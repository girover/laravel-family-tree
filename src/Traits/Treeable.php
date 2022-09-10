<?php

namespace Girover\Tree\Traits;

use Girover\Tree\Database\NodeQuerySql;
use Girover\Tree\GlobalScopes\OrderByLocationScope;
use Girover\Tree\Location;
use Girover\Tree\Pointer;
use Girover\Tree\Services\treeableService;
use Illuminate\Support\Facades\DB;

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
        return $this->pointer()->node() === null ? true : false;
    }

    /**
     * Move the pointer to indicates to the root of this tree
     *
     * @return \Girover\Tree\Pointer|null
     */
    public function pointerToRoot()
    {
        return $this->pointer()
                    ->to((nodeableModel())::tree($this->getKey())
                    ->whereNull('node_parent_id')->first());
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
        return $this->nodesQuery()->where('node_id', $this->main_node)
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
            $this->main_node = $node->node_id;
            $this->save();
            return $node;
        }
        $main_node = $this->nodesQuery()->where('node_id', $node)->first();

        if ($main_node) {
            $this->main_node = $node;
            $this->save();
            return $node;
        }
        return false;
    }

    /**
     * Get all Nodes of this Tree from database
     * sorted by path of nodes
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllNodes()
    {
        // Eager loading the relations `wives`
        return ((nodeableModel())::fromQuery(NodeQuerySql::treeNodesSortedByPathSql($this->getKey())))->load('wives');
    }

    /**
     * Get all nodes in this tree from the database
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function nodes()
    {
        return $this->getAllNodes();
    }

    /**
     * We get All Nodes that are in specific generation depending on given number
     *
     * @param int $generation
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function nodesOfGeneration($generation = 1)
    {
        return ((nodeableModel())::fromQuery(NodeQuerySql::treeGenerationNodes($this->getKey(), $generation)))->load('wives');
    }

    /**
     * Get the root node of this tree from the database
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function root()
    {
        return $this->nodesQuery()->whereNull('node_parent_id')->first(); 
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
     * To count the nodes in this tree by querying database
     *
     * @return int
     */
    public function countNodesFromPointer()
    {
        // if pointer is free, move it to the root
        if ($this->isPointerFree()) {
            $this->pointerTo($this->root());
        }
        
        return DB::select(NodeQuerySql::subtreeCountNodes($this->pointer()->node()))[0]->total_nodes;
    }

    /**
     * Indicate How many generation this tree has {The entire tree}
     *
     * @return int | NULL
     */
    public function countGenerations()
    {
        return DB::select(NodeQuerySql::treeCountGenerations($this->getKey()))[0]->total_generations;
    }

    /**
     * Move the pointer of the tree to a given node
     *
     * @param \Illuminate\Database\Eloquent\Model
     * @return \Girover\Tree\Pointer
     */
    public function pointerTo($nodeable)
    {
        return $this->pointer()->to($nodeable);
    }

    /**
     * Move the pointer of the tree to a given node
     *
     * @param \Illuminate\Database\Eloquent\Model
     * @return $this
     */
    public function goTo($nodeable)
    {
        return $this->pointerTo($nodeable);
    }

    /**
     * Get the newest generation members in the tree
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function leafNodes()
    {
        return ((nodeableModel())::fromQuery(NodeQuerySql::treeLeafNodes($this->getKey())))->load('wives');
    }

    /**
     * Draw the current tree and return it as HTML.
     *
     * @return string
     */
    public function toTree()
    {
        return $this->build();
    }

    /**
     * Draw the current tree and return it as HTML.
     *
     * @return string
     */
    public function toHtml()
    {
        return $this->build();
    }

    /**
     * rendering the current tree as HTML string.
     *
     * @return string
     */
    public function render()
    {
        return $this->build();
    }

    /**
     * Draw the current tree and return it as HTML
     *
     * @return string
     */
    public function build()
    {
        return $this->treeableService()->buildTree();
    }


    // NEW
    public function getForeignKeyName()
    {
        $this->foreign_key;
    }
}
