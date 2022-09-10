<?php

namespace Girover\Tree\Services;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
use Girover\Tree\Models\TreeNode;
use \Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NodeableService
{
    /**
     * @var \Illuminate\Database\Eloquent\Model
     * the model that represents nodeable models
     */
    protected $nodeable;

    /**
     * Constructor
     * @param null|\Illuminate\Database\Eloquent\Model $nodeable
     */
    public function __construct($nodeable = null) {
        $this->nodeable = $nodeable;
    }

    /**
     * set the node
     * 
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function nodeable(Model $nodeable) {
        $this->nodeable = $nodeable;

        return $this;
    }

    /**
     * Determine if the provider gender
     * is a valid gender
     * @param string $gender
     * @return void
     * @throws Girover\Tree\Exceptions\TreeException
     */
    public function validateGender($gender)
    {
        if ($gender !== male() && $gender !== female()) {
            throw new TreeException("Invalid gender is provided", 1);            
        }
    }

    /**
     * Create new node
     *
     * @param \Illuminate\Database\Eloquent\Model
     * @param string $location the location of the new [child|sibling|...]
     * @param int $treeable_id the id of the treeable model
     * @param string $gender the gender of the person
     * 
     * @throws \Girover\Tree\Exceptions\TreeException
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createNewNode($data, $node_parent_id, $gender = null)
    {
        $gender = ($gender == '') ? male() : $gender;

        $this->validateGender($gender);

        try {
            DB::beginTransaction();

            $nodeable = $this->createNodeable($data, $gender);
            
            $node     = $this->createNode([
                                'nodeable_id'=>$nodeable->getKey(), 
                                'treeable_id'=>$this->nodeable->treeable_id, 
                                'node_parent_id'=>$node_parent_id
                            ]);

            // if(!$nodeable || !$node){
            if(!$node){
                throw new TreeException("Failed to create the node", 1);               
            }

            DB::commit();

            // To join node attributes with nodeable attributes
            $node_columns =Schema::getColumnListing('tree_nodes');
            foreach ($node_columns as $column) {
                if($column != 'created_at' && $column != 'updated_at'){
                    $nodeable->{$column}=$node->{$column};
                }
            }

            return $nodeable;

        } catch (\Throwable $th) {            
            DB::rollBack();
            throw $th;            
        }
    }

    /**
     * Creating new nodeable if not exists
     * If exists then return it
     * 
     * @param \Illuminate\Database\Eloquent\Mode|null
     * @return \Illuminate\Database\Eloquent\Mode
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    public function createNodeable($data, $gender)
    {
        if ($data instanceof (nodeableModel())) {

            // $data is a model and already exists in database
            if ($data->exists){
                return $data;
            }

            $data->{gender()} = $gender;
            $data->save();

            return $data;
        }

        if (! is_array($data)) {
            throw new TreeException("Bad argument type. The argument passed to ".__METHOD__." must be an array or an instance of [".nodeableModel()."]. ".gettype($data)." is given", 1);
        }

        if (empty($data)) {
            throw new TreeException("No data are provided for creating the nodeable", 1);
        }

        $data[gender()] = $gender;

        return (nodeableModel())::create($data);
    }

    /**
     * creating new node for the nodeable model
     * 
     * @param array|\Illuminate\Database\Eloquent\Model $dta
     * 
     * @return Girover\Tree\Models\Node
     */
    public function createNode($data)
    {
        return TreeNode::create($data);
    }

    /**
     * Create father for the root node in the tree
     *
     * @param \Illuminate\Database\Eloquent\Model $root data for the new father
     * 
     * @return mixed
     */
    public function createFather($data)
    {
        if (! $this->nodeable->isRoot()) {
            throw new TreeException("Error: Can't make father for node:[ ".$this->nodeable->location." ]. node should be root to make father for it.", 1);
        }

        $new_root = $this->createNewNode($data, null, male());

        $this->nodeable->node_parent_id = $new_root->node_id;
        $this->nodeable->save();

        return $new_root;
    }

    /**
     * Create new child for this node
     *
     * @param array|static data for the new child
     * @param string $gender 'm'|'f'
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function newChild($data, $gender = '')
    {
        return $this->createNewNode($data, $this->nodeable->node_id, $gender);
    }

    /**
     * Create new sibling for this node
     * by default the new node is son.
     *
     * @param array|static data for the new sibling
     * @param string gender of the new sibling
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function newSibling($data, $gender = '')
    {
        if ($this->nodeable->isRoot()) {
            throw new TreeException("Cannot add sibling to the Root of the tree.", 1);
        }

        return $this->createNewNode($data, $this->nodeable->node_parent_id, $gender);
    }

    /**
     * assign a wife to this node
     *
     * @param \Illuminate\Database\Eloquent\Model $wife
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function getMarriedWith($wife)
    {
        if (! $wife instanceof (nodeableModel())) {
            throw new TreeException("Argument must be instance of ".(nodeableModel())::class.".", 1);
        }
        if (! $wife->exists) {
            throw new TreeException("This wife is not saved in database. First save it", 1);
        }
        // only male nodes allowed to do this
        if ($this->nodeable->isFemale()) {
            throw new TreeException("Only men are allowed to use this".__METHOD__, 1);
        }
        if ($wife->isNode()) {
            // Person cannot get married with himself
            if ($wife->isMale()) {
                throw new TreeException("Man is not allowed to get married with a man ".__METHOD__, 1);
            }
        }

        // Person already married with the given woman
        $mar = $this->nodeable->wives()
                              ->where('nodeable_wife_id', $wife->getKey())
                              ->where('nodeable_husband_id', $this->nodeable->getKey())
                              ->first() ? throw new TreeException("These two nodes are already married!!", 1)
                                        : false;

        // Person cannot get married with himself
        if ($this->nodeable->getKey() === $wife->getKey()) {            
            throw new TreeException('Person cannot get married with himself', 1);    
        }
        
        return $this->nodeable->wives()->attach($wife->getKey());
    }

    /**
     * Check if nodeable is married with the given nodeable
     * @param Illuminate\Database\Eloquent\Model
     * 
     * @return bool 
     * @throws Girover\Tree\Exceptions\TreeException
     */
    public function isMarriedWith($wife)
    {
        if ($this->nodeable->isMale()) {

            if ($wife->isMale()) {
                return false;
            }

            return (bool)$this->nodeable->wives()->where('marriages.nodeable_wife_id', $wife->getKey())
                                       ->count();
        }

        if ($wife->isFemale()) {
            throw new TreeException("Woman Can not be married with a woman", 1);
        }

        return (bool)$this->nodeable->husband()->where('nodeable_husband_id', $wife->getKey())
                                   ->count();
    }

    /**
     * Throw an exception when trying to get som of these
     * methods if the nodeable model is not connected with a node yet
     * 
     * @throws Girover\Tree\Exceptions\TreeException
     */
    public function throwExceptionIfNotNode()
    {
        if (!$this->nodeable->isNode()) {
            throw new TreeException("This model is not a node yet!!!", 1);            
        }
    }

    /**
     * Check if two nodeables belong the same tree
     * 
     * @return bool
     */
    public function areFromSameTree($nodeable1, $nodeable2)
    {
        return $nodeable1->treeable_id === $nodeable2->treeable_id ? true : false;
    }

    /**
     * To build a tree as Html from specific node
     * 
     * @return string html representing the tree starting from a node
     * 
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    public function buildTreeFromANode()
    {
        $this->throwExceptionIfNotNode();

        $tree = (treeableModel())::find($this->nodeable->treeable_id);
        
        $tree->pointer()->to($this->nodeable);

        return $tree->build();
    }

    /**
     * Make a node as child of another node
     * They should belong the same tree
     *
     * @param \Illuminate\Database\Eloquent\Model $node
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    public function makeAsChildOf($nodeable)
    {
        if ($nodeable->isFemale()) {
            throw new TreeException("Error: Not allowed to add children to female nodes.", 1);
        }
        // Not allowed to move children from an ancestor to a descendant.
        if ($this->nodeable->isAncestorOf($nodeable)) {
            throw new TreeException("Error: Not allowed to move children from an ancestor to a descendant.", 1);
        }
        // Not allowed to move a node to its father.
        if ($nodeable->isFatherOf($this->nodeable)) {
            throw new TreeException("The nodeable is already child of the target nodeable.", 1);
        }

        $this->nodeable->node_parent_id = $nodeable->node_id;
        $this->nodeable->save();

        return $this->nodeable;
    }

    /**
     * Move all children of the node to be children of
     * the given node or the node that has the given location.
     *
     * @param \Illuminate\Database\Eloquent\Model
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function moveChildrenTo($nodeable)
    {
        // Not allowed to add children to female nodes.
        if ($nodeable->isFemale()) {
            throw new TreeException("Error: Not allowed to add children to female nodes.", 1);
        }
        // Not allowed to move children from an ancestor to a descendant.
        if ($this->nodeable->isAncestorOf($nodeable)) {
            throw new TreeException("Error: Not allowed to move children from an ancestor to a descendant.", 1);
        }
        
        $children = $this->nodeable->children();
        
        TreeNode::whereIn('node_id', $children->pluck('node_id')->toArray())
                ->update(['node_parent_id'=>$nodeable->node_id]);

        return $this;
    }

    /**
     * To detach a node from a tree
     * it will delete the node from tree_nodes table,
     * but nodeable will still exist.
     * 
     * @return bool
     */
    public function detachFromTree()
    {
        return TreeNode::where('node_id', $this->node_id)->delete();
    }

}