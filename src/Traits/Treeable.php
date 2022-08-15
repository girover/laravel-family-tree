<?php

namespace Girover\Tree\Traits;

use Girover\Tree\GlobalScopes\OrderByLocationScope;
use Girover\Tree\Location;
use Girover\Tree\Pointer;
use Girover\Tree\Services\treeableService;

/**
 *  The model `Tree` has to use this trait
 */
trait Treeable
{ 
    /**
     * @var \Girover\Tree\Services\treeableService
     */
    public $treeable_service;
    

    /**
     * @var int treeable id in table 'nodes'
     */
    protected $foreign_key = 'treeable_id';

    /**
     * represent a node in the tree [current node]
     *
     * @var \Girover\Tree\Pointer|null
     */
    private  $pointer = null;

    /**
     * @var \Illuminate\Database\Eloquent\Collection|null
     */
    protected $nodes = null;

    /**
     * Getting instance of treeableService to deal with treeable functionality
     * 
     * @return \Girover\Tree\Services\treeableService
     */
    public function treeableService()
    {
        return $this->treeable_service ?? (new TreeableService($this));
    }

    public static function bootTreeable()
    {
        
    }

    /**
     * Every time an instance of \Girover\Tree\Models\Tree creates
     * this method will be invoked
     * @return void
     */
    public function initializeTreeable()
    {
        // $this->fillable = array_merge($this->fillable, static::$fillable_cols);
    }

    /**
     * Tree has a pointer. create and make it free
     *
     * @return void
     */
    protected function makePointer()
    {
        $this->pointer = new Pointer($this);
    }

    /**
     * get the pointer of the tree
     * @return \Girover\Tree\Pointer
     */
    public function pointer()
    {
        if ($this->pointer === null) {
            $this->makePointer();
        }
        return $this->pointer;
    }

    /**
     * Determine if the pointer is not indicating to any node yet.
     * 
     * @return bool
     */
    public function isPointerFree()
    {
        return $this->pointer()->location() === null ? true : false;
    }

    /**
     * Move the pointer to indicates to the root of this tree
     *
     * @return \Girover\Tree\Pointer|null
     */
    public function pointerToRoot()
    {
        return $this->pointer()->to((nodeableModel())::tree($this->getKey())->first());
    }

    /**
    * Make database query for The Node model to start other queries from this point
    *
    * @return \Girover\Tree\Database\Eloquent\NodeEloquentBuilder
    */
    public function nodesQuery()
    {
        return (nodeableModel())::where($this->foreign_key, $this->getKey());
    }

    /**
     * Determine if there are nodes in this tree
     *
     * @return bool
     */
    public function isEmptyTree()
    {
        return ($this->nodesQuery()->count() == 0) ? true : false;
    }

    /**
     * Determine if the Tree has node with this given location
     *
     * @param string $location : location of node
     * @return int [1, 0]
     *
     */
    public function has($location)
    {
        return $this->nodesQuery()
                    ->where('location', $location)->count();
    }

    /**
     * To unload this tree.
     *
     * @return void
     */
    public function unload()
    {
        $this->nodes = null;
        $this->pointer()->toRoot();
    }

    /**
     * Get the main node in the current tree
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function mainNode() // Should Removes
    {
        return $this->nodesQuery()->where('location', $this->main_node)
                                    ->where($this->foreign_key, $this->getKey())
                                    ->first();
    }

    /**
     * set the node that has the given location to be main node in this tree
     * @param \Illuminate\Database\Eloquent\Model|string $node
     * @return \Illuminate\Database\Eloquent\Model|false
     */
    public function setMainNode($node) // Should Removes
    {
        if ($node instanceof (nodeableModel())) {
            $this->main_node = $node->location;
            $this->save();
            return $node;
        }
        $main_node = $this->nodesQuery()->where('location', $node)->first();

        if ($main_node) {
            $this->main_node = $node;
            $this->save();
            return $node;
        }
        return false;
    }

    /**
     * Get all Nodes in this Tree from database Table 'nodes'
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllNodes()
    {
        return $this->nodesQuery()->get();
    }

    /**
     * Get All Nodes that are in specific generation depending on given number
     *
     * @param int $generation
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function nodesOfGeneration($generation = 1)
    {
        return $this->nodesQuery()->locationREGEXP(Location::singleGenerationREGEXP($generation))
                    ->get();
    }

    /**
     * Get the root node of this tree from the database
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function root()
    {
        return $this->nodesQuery()->first(); 
    }

    /**
     * Create the root for this tree.
     *
     * @param array $data data of the root node
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    public function createRoot($data = [])
    {
        return $this->treeableService()->createRoot($data);
    }

    /**
     * Create new Root node in this tree and make
     * the current root child of the new root
     *
     * @param array $data
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function newRoot($data = [])
    {
        if (empty($data)) {
            return null;
        }

        if ($this->isEmptyTree()) {
            return $this->createRoot($data);
        }
        
        return $this->pointer()->toRoot()->createFather($data);
    }

    /**
     * Get all nodes in this tree from the database
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function nodes()
    {
        return $this->nodesQuery()->get();
    }

    /**
     * To count the nodes in this tree by querying database
     *
     * @return int
     */
    protected function countDatabaseNodes()
    {
        return $this->nodesQuery()
                    ->where('location', 'like', $this->pointer()->location().'%')
                    ->count();
    }

    /**
     * Indicate How many generation this tree has {The entire tree}
     *
     * @return int | NULL
     */
    public function countGenerations()
    {
        $node = $this->nodesQuery()
                     ->withoutGlobalScope(OrderByLocationScope::class)
                     ->orderByRaw('LENGTH(location) DESC')->first();

        if (null !== $node) {
            return Location::generation($node->location);
        }

        return 0;
    }

    /**
     * Move the pointer of the tree to a given node or location
     *
     * @param \Illuminate\Database\Eloquent\Model|string $location
     * @return \Girover\Tree\Pointer
     */
    public function pointerTo($location)
    {
        return $this->pointer()->to($location);

        // return $this;
    }
    /**
     * Move the pointer of the tree to a given node or location
     *
     * @param \Illuminate\Database\Eloquent\Model|string $location
     * @return $this
     */
    public function goTo($location)
    {
        return $this->pointerTo($location);
    }

    /**
     * Get nodes who have the longest location in the tree
     * Get the newest generation members in the tree
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function nodesOnTop()
    {
        return $this->nodesQuery()
                    ->withoutGlobalScope(OrderByLocationScope::class)
                    ->orderByRaw('LENGTH(location) DESC')->get();
    }

    /**
     * Draw the current tree and return it as HTML.
     *
     * @return string
     */
    public function toTree()
    {
        return $this->draw();
    }

    /**
     * Draw the current tree and return it as HTML.
     *
     * @return string
     */
    public function toHtml()
    {
        return $this->draw();
    }

    /**
     * Draw the current tree and return it as HTML
     *
     * @return string
     */
    public function draw()
    {
        return $this->treeableService()->buildTree();
    }


    // NEW
    public function getForeignKeyName()
    {
        $this->foreign_key;
    }
}
