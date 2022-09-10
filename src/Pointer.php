<?php
/**----------------------------------------------------------------------------------
| Pointer of Tree
| Auth: Majed Girover
|        girover.mhf@gmail.com
| ---------------------------------------------------------------------------------
| when creating a FamilyTree instance, a pointer object is created and indicates
| to the root node in the Tree.
| the pointer only indicates to nodes in the database table and not to the loaded nodes
| inside the tree property $nodes.
| so every time Pointer moves through the tree it make request to database table and return
| one record of tree nodes.
| therefor be aware when you draw the tree to show in the browser, because
| if the tree nodes are already loaded and you move the pointer through the tree
| and you want to draw them again, so it will draw the old nodes those were loaded before.
| And to draw the tree from the pointer node, you have to call `load` method to change
| the already loaded nodes.
|
| */

namespace Girover\Tree;

use BadMethodCallException;
use Girover\Tree\Database\Sql\Delete;
use Girover\Tree\Exceptions\TreeException;
use Illuminate\Support\Facades\DB;

class Pointer
{
    /**
     * Instance of Girover\Tree\FamilyTree
     * ---------------------------------------------------------------------------------
     * when creating a pointer it take an instance of FamilyTree as parameter
     * @var \Girover\Tree\Traits\Treeable|null
     */
    protected $tree;

    /**
     * The node that pointer indicates to
     * 
     * @var \Illuminate\Database\Eloquent\Model|null
     */
    protected $node;

    /**
     * Initialize the Pointer and indicate it to the root node
     * of the given tree
     *
     * @param \Girover\Tree\Models\Tree |null $indicated_node
     *
     * @return void
     */
    // public function __construct(FamilyTree $family_tree, Node $indicated_node = null)
    public function __construct($tree = null)
    {
        $this->tree = $tree;
    }

    /**
     * Calling methods of class Girover\Tree\Models\Node
     *
     * @param string $name method name
     * @param array $args parameters that passed to the called method
     * @return mixed
     *
     * @throws \Girover\Tree\Exceptions\TreeException
     * @throws \BadMethodCallException
     */
    public function __call($name, $args)
    {
        if ($this->node() === null && method_exists(nodeableModel(), $name)) {
            throw new TreeException("Pointer is not indicating to any node in Tree [".$this->tree->name."].", 1);
        }

        if ($this->node() instanceof (nodeableModel())) {
            if (method_exists(nodeableModel(), $name)) {
                return call_user_func([$this->node(), $name], ...$args);
            }
        }

        throw new BadMethodCallException('Call undefined method [ '.$name.' ] on class: '.nodeableModel());
    }

    /**
     * Make query start point to database table [ model -> Nodes ]
     *
     * starts with select from `nodes` where treeable_id = {current tree id}
     *
     * @return \Girover\Tree\Database\Eloquent\NodeEloquentBuilder
     */
    public function nodesQuery()
    {
        return $this->tree->nodesQuery();
    }

    /**
     * Get the node that the Pointer indicates to
     *
     * @return \Girover\Tree\Models\Node|null
     */
    public function node()
    {
        return $this->node;
    }

    /**
     * Get the node that the Pointer indicates to
     *
     * @return \Girover\Tree\Models\Node|null
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * Get the properties of the tree model
     *
     * @return \Girover\Tree\Models\Tree|null
     */
    public function tree()
    {
        return $this->tree;
    }

    /**
     * Move the Pointer to the given location
     *
     * @param \Illuminate\Database\Eloquent\Model $nodeable
     * @return \Girover\Tree\Pointer $this
     *
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    public function to($nodeable)
    {
        // if ($location instanceof ($this->model())) {
        if (! $nodeable instanceof (nodeableModel())) {
            throw new TreeException("Passed node to the method [". __METHOD__ ." ] should be instance of". nodeableModel(), 1);                
        }
        if ($this->tree->getKey() !== $nodeable->treeable_id) {
            throw new TreeException("Passed node to the method [". __METHOD__ ." ] don't belong the tree", 1);                
        }

        $this->node = $nodeable;

        return $this;
    }

    /**
     * Move the Pointer to the Root location of this tree
     *
     * @return \Girover\Tree\Pointer $this
     */
    public function toRoot()
    {
        $root = nodeableModel()::where('treeable_id', $this->tree->getKey())
                               ->whereNull('node_parent_id')->first();

        return $this->to($root);
    }

    /**
     * Move the Pointer back two steps to indicate to its own grandfather.
     *
     * @return \Girover\Tree\Pointer $this
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    public function toGrandfather()
    {
        $grandfather = $this->node()->grandFather();

        if ($grandfather) {
            return $this->to($grandfather);
        }

        throw new TreeException("The node has no grandfather in the tree.", 1);
    }

    /**
     * Move the Pointer back one step to indicate to its own father.
     *
     * @return \Girover\Tree\Pointer $this
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    public function toFather()
    {
        if ($this->indicatesToRoot()) {
            throw new TreeException("Root node has no father to move the pointer to.", 1);
        }

        return $this->to($this->node()->father());
    }

    /**
     * Run the Pointer to indicate to its own last child.
     *
     * @return \Girover\Tree\Pointer $this
     */
    public function toFirstChild()
    {
        if (($first_child = $this->firstChild()) === null) {
            throw new TreeException("Error: Node has no children to move to the first one.", 1);
        }

        return $this->to($first_child);
    }

    /**
     * Move the Pointer to indicate to last child of the current node.
     *
     * @return \Girover\Tree\Pointer $this
     *
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    public function toLastChild()
    {
        if (($last_child = $this->node()->lastChild()) === null) {
            throw new TreeException("Node has no children.", 1);
        }

        return $this->to($last_child);
    }

    /**
     * Determine if the node that the pointer is indicating to is the Root node
     *
     * @return bool
     */
    public function indicatesToRoot()
    {
        return $this->indicatesToNull() 
               ? false 
               : $this->node()->isRoot();
    }

    /**
     * Determine if pointer is indicating to NULL.
     *
     * @return bool
     */
    public function indicatesToNull()
    {
        return $this->node === null ? true : false;
    }

    /**
     * To get all nodes from the pointer including the node that pointer indicates to
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all()
    {
        return $this->tree->allFromPointer();
    }

    /**
     * Get the longest location in the tree that starts from the pointer
     *
     * @return object|string|null
     */
    public function longestLocation()
    {
        return $this->nodesQuery()
                    ->where('location', 'like', $this->location().Location::SEPARATOR.'%')
                    ->orderByRaw('LENGTH(location) DESC')
                    ->orderBy('location', 'desc')
                    ->first()
                    ->location;
    }

    /**
     * Get the location of the father of the current node
     *
     * separate location of current node by last "."
     *
     * @return string | Null
     */
    public function fatherLocation()
    {
        // return father location of node that pointer is indicating to
        return ($this->indicatesToRoot() || $this->indicatesToNull())
               ? null
               : Location::father($this->location());
    }

    /**
     * Get the last segment of node location: section that is after the last separator
     *
     * @return string
     */
    public function self()
    {
        return $this->indicatesToRoot() 
               ? $this->node->location 
               : substr($this->location(), strrpos($this->location(), Location::SEPARATOR) + 1);
    }

    /**
     * Convert the current location to REGEXP pattern
     * If the given parameter is true so wrap the pattern with begin and end flags
     *
     * @param bool $covered to cover the pattern or not
     * example aaa.bbb = aaa\.bbb
     * if $covered==true aaa.bbb = (^aaa\.bbb$)
     * @return string REGEXP pattern
     */
    public function getAsRegPattern($covered = false)
    {
        $location_pattern = str_replace(Location::SEPARATOR, '\\'.Location::SEPARATOR, $this->location());

        return $covered ? '(^'.$location_pattern.'$)' : $location_pattern;
    }

    /**
     * How many generation does this tree has {The sub tree from the pointer}
     * Depends on the counting separator in the location
     *
     * @return int
     */
    public function countGenerationsFromHere()
    {
        $current = Location::generation($this->location());
        $longest = Location::generation($this->longestLocation());

        return $longest - $current + 1;
    }

    /**
     * Indicate which generation the node that the pointer indicates to, has.
     *
     * @return Int | NULL
     */
    public function generationNumber()
    {
        return Location::generation($this->location());
    }

    /**
     * How many generation does this tree has {The sub tree from the pointer including pointer`s node}
     *
     * @return int|null
     */
    public function totalGenerations()
    {
        // generation of longest location in tree
        $total_generations = $this->tree->totalGenerations();
        // generation of the pointer location
        $sub_tree_generations = $this->generationNumber();
        // find the deference between the both. Add 1 to include the pointer`s node
        return $total_generations - $sub_tree_generations + 1;
    }

    public function getGenerations($generations_count)
    {
        return $generations_count;
    }

    /**
     * to make the tree instance loads its own nodes from Pointer's node location
     *
     * @param int|null $generations
     * @return \Girover\Tree\Pointer
     */
    public function load($generations = null)
    {
        $this->tree->load($generations);

        return $this;
    }

    /**
     * To make the node that the pointer indicates to as a basic node
     * in the tree of this pointer
     *
     * @return $this
     */
    public function makeAsBasicNode()
    {
        $this->tree->basic_node = $this->location();
        $this->tree->save();

        return $this;
    }

    /**
     * To delete the node with its children
     *
     * @return int affected rows
     */
    public function deleteWithChildren()
    {
        return DB::delete(Delete::nodeWithChildren($this->tree->id, $this->location()));
    }

    /**
     * Get the tree as Html
     *
     * @return string
     */
    public function toTree()
    {
        return $this->build();
    }

    /**
     * Get the tree as Html
     *
     * @return string
     */
    public function toHtml()
    {
        return $this->build();
    }

    /**
     * Building the tree to Html string
     *
     * @return string
     */
    public function build()
    {
        return $this->tree->build();
    }

    /**
     * Rendering the tree as Html
     *
     * @return string
     */
    public function render()
    {
        return $this->build();
    }
}