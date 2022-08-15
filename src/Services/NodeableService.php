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
        if ($gender !== 'm' && $gender !== 'f') {
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
    public function createNewNode($data, $location, $gender = 'm')
    {
        Location::validate($location);

        $this->validateGender($gender);

        try {
            DB::beginTransaction();

            $nodeable = $this->createNodeable($data) ?? throw new TreeException("Failed to insert data", 1);
            
            $node     = $this->createNode(['nodeable_id'=>$nodeable->getKey(), 'treeable_id'=>$this->nodeable->treeable_id, 'location'=>$location, 'gender'=>$gender]);

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

    // NEW
    public function createNodeable($data)
    {
        if ($data instanceof (nodeableModel())) {

            // $data is a model and exists in database
            if ($data->exists){
                return $data;
            }

            $data->save();

            return $data;
        }

        if (! is_array($data)) {
            throw new TreeException("Bad argument type. The argument passed to ".__METHOD__." must be an array or an instance of [".nodeableModel()."]. ".gettype($data)." is given", 1);
        }

        if (empty($data)) {
            throw new TreeException("No data are provided for creating the nodeable", 1);
        }
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
     * @param array $data data for the new father
     * 
     * @return mixed
     */
    public function createFather($data)
    {
        if (! $this->nodeable->isRoot()) {
            throw new TreeException("Error: Can't make father for node:[ ".$this->nodeable->location." ]. node should be root to make father for it.", 1);
        }

        DB::beginTransaction();

        try {
            // update all nodes locations in this tree.
            // Firstly prepend all locations with separator '.'
            // to prevent duplicated locations in same tree
            DB::update($this->prependLocationsWithSeparatorSql(), [$this->nodeable->treeable_id]);
            // then prepend all locations with 'aaa'
            DB::update($this->prependLocationsWithFirstPossibleSegmentSql(), [$this->nodeable->treeable_id]);

            // create th new root node with given data
            $new_root = $this->createNewNode($data, Location::firstPossibleSegment(), 'm');

            DB::commit();

            return $new_root;
        } catch (\Throwable $th) {
            // Rollback the database changes
            DB::rollBack();

            throw new TreeException("Error: ".$th->getMessage(), 1);
        }
    }

    /**
     * Create new child for this node
     *
     * @param array|static data for the new child
     * @param string $gender 'm'|'f'
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function newChild($data, $gender = 'm')
    {
        $new_child_location = $this->newChildLocation();

        return $this->createNewNode($data, $new_child_location, $gender);
    }

    /**
     * Generating location for new child for this node.
     * 
     * @return string
     */
    protected function newChildLocation()
    { 
        return $this->newChildLocationFor($this->nodeable);
        // return ($last_child = $this->nodeable->lastChild())
        //        ? Location::generateNextLocation($last_child->location)
        //        : Location::firstChild($this->nodeable->location);
    }

    /**
     * Generating location for new child for this node.
     * 
     * @param \Illuminate\Database\Eloquent\Model
     * @return string
     */
    protected function newChildLocationFor($node)
    {  
        return ($last_child = $node->lastChild())
               ? Location::generateNextLocation($last_child->location)
               : Location::firstChild($node->location);    
    }

    /**
     * Create new sibling for this node
     * by default the new node is son.
     *
     * @param array|static data for the new sibling
     * @param string gender of the new sibling
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function newSibling($data, $gender = 'm')
    {
        if ($this->nodeable->isRoot()) {
            throw new TreeException("Cannot add sibling to the Root of the tree.", 1);
        }

        if ($this->nodeable->hasSiblings()) {
            $new_sibling_location = Location::generateNextLocation($this->nodeable->lastSibling()->location);
        } else {
            $new_sibling_location = Location::generateNextLocation($this->nodeable->location);
        }

        Location::validate($new_sibling_location);

        return $this->createNewNode($data, $new_sibling_location, $gender);
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
                throw new TreeException("Man Can not be married with a man", 1);
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

        return $tree->draw();
    }

    /**
     * Move the node with its children
     * to be child of the given location
     * Both nodes should belong to same tree
     *
     * @param \Illuminate\Database\Eloquent\Model $node
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    protected function makeAsChildOf($node)
    {
        // Generate new location for this node
        $new_location = $this->newChildLocationFor($node);

        // Update locations of this node and its children in database
        $this->nodeable->updateLocation($new_location);
        // $this->updateLocation($new_location);

        $this->nodeable->location = $new_location;

        return $this->nodeable;
    }

        /**
     * Move the node with its children
     * to be child of the given location
     * Both nodes should belong to same tree
     *
     * @param \Illuminate\Database\Eloquent\Model|string $location: location or node to move node to it
     * 
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    public function moveTo($location)
    { 
        $nodeable = $this->getNodeOrThrowException($location);
        // Not allowed to add children to female nodes.
        if ($nodeable->isFemale()) {
            throw new TreeException("Error: Not allowed to add children to female nodes.", 1);
        }
        // Not allowed to move children from an ancestor to a descendant.
        if ($this->nodeable->isAncestorOf($nodeable)) {
            throw new TreeException("Error: Not allowed to move children from an ancestor to a descendant.", 1);
        }
        // Not allowed to move a node to its father.
        if ($nodeable->isFatherOf($this->nodeable)) {
            throw new TreeException("Error: Not allowed to move a node to its father.", 1);
        }

        return $this->makeAsChildOf($nodeable);
    }


    /**
     * Move all children of the node to be children of
     * the given node or the node that has the given location.
     *
     * @param \Illuminate\Database\Eloquent\Model|string $location
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function moveChildrenTo($location = null)
    {
        $node = $this->getNodeOrThrowException($location);

        // Not allowed to add children to female nodes.
        if ($node->isFemale()) {
            throw new TreeException("Error: Not allowed to add children to female nodes.", 1);
        }
        // Not allowed to move children from an ancestor to a descendant.
        if ($this->nodeable->isAncestorOf($node)) {
            throw new TreeException("Error: Not allowed to move children from an ancestor to a descendant.", 1);
        }
        
        $children = $this->nodeable->children();
        
        DB::beginTransaction();
        try {
            foreach ($children as $child) {
                $child->moveTo($location);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();            
            throw new TreeException($th->getMessage(), 1);            
        }

        return $this;
    }

    /**
     * To get a node from a location
     * Or to check if the given parameter is a node then return it
     * 
     * @param \Illuminate\Database\Eloquent\Model|string $location
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    protected function getNodeOrThrowException($location)
    {
        if ($location instanceof (nodeableModel())) {
            return $location;
        }

        return  (nodeableModel())::tree($this->nodeable->treeable_id)->location($location)->first()
                ?? throw new TreeException("Error: The location `".$location."` not found in this tree.", 1);
    }

    /**
     * Sql UPDATE statement to add SEPARATOR '.'
     * to the beginning of all locations in the given tree
     *
     * @param int $tree_id
     * @return string sql statement
     */
    public function prependLocationsWithSeparatorSql()
    {
        // return 'UPDATE '. static::table() .
        return ' UPDATE `tree_nodes` '.
               ' SET `location` = CONCAT("'.Location::SEPARATOR.'", `location`)'.
               ' WHERE treeable_id = ? ';
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
        return DB::delete($this->deleteNodeWithChildrenSql());
    }

    /**
     * Sql UPDATE statement to add first possible segment
     * 'aaa'|'000' to the beginning of all locations in the given tree
     * NOTE: this method doesn't add '.' to the segment
     *
     * @param int $tree_id
     * @return string sql statement
     */
    public function prependLocationsWithFirstPossibleSegmentSql()
    {
        return ' UPDATE `tree_nodes`' .
               ' SET `location` = CONCAT("'.Location::firstPossibleSegment().'", `location`)'.
               ' WHERE treeable_id = ? ';
    }

    /**
     * Sql statement for updating location column in the tree_nodes table.
     * Find all locations that start with $old_location
     * and concat them with $new_location to beginning of old location.
     *
     * Note: when changing location so all descendants that are
     * connected under this location have to be changed too.
     *
     * @example replace 'aa.ff' with 'bb.ss'
     *
     *          UPDATE `tree_nodes`
     *          SET `location` = CONCAT('bb.ss', SUBSTRING(`location`, FROM 6))
     *          WHERE `tree_id` = 2
     *          AND `location` like 'aa.ff%' ;
     * @param string $new_location
     * @return string
     */
    public function updateLocationsSql($new_location)
    {
        return " UPDATE `tree_nodes` 
                SET `location` = CONCAT('".$new_location."', SUBSTRING(`location` FROM ".(strlen($this->nodeable->location) + 1).")) 
                WHERE `treeable_id` = ".$this->nodeable->treeable_id." 
                AND `location` like '".$this->nodeable->location."%' ";
    }

    /**
     * To delete nodes with its children from database
     *
     * @param int $tree_id
     * @param int $location
     * @return string
     */
    public function deleteNodeWithChildrenSql()
    {
        return " DELETE FROM `tree_nodes`   
                WHERE `treeable_id` = ".$this->nodeable->treeable_id." 
                AND `location` like '".$this->nodeable->location."%' ";
    }

    /**
     * Updating the location of the node
     * this will update location for all the node's descendants too.
     * 
     * @return int
     */
    public function updateLocation($new_location)
    {
        return DB::update($this->updateLocationsSql($new_location));
    }
}