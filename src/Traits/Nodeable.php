<?php

namespace Girover\Tree\Traits;

use Girover\Tree\Database\Eloquent\NodeEloquentBuilder;
use Girover\Tree\Database\NodeQuerySql;
use Girover\Tree\Database\TreeQueryBuilder;
use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\GlobalScopes\WivesEagerRelationScope;
use Girover\Tree\Location;
use Girover\Tree\Models\TreeNode;
use Girover\Tree\NodeRelocator;
use Girover\Tree\Services\NodeableService;
use Illuminate\Support\Facades\DB;

/**
 *
 */
trait Nodeable
{

    /**
     * @var \Girover\Tree\Services\NodeableService
     */
    protected $nodeable_service;

    /**
     * to control deleting children or not when deleting a node
     * false: delete node with its children
     * true: delete only the node, but move its children to be children for its father 
     *
     * @var bool
     */
    protected $on_delete_shift_children = false;

    /**
     * Getting instance of NodeableService to deal with nodeable models functionality
     * 
     * @return \Girover\Tree\Services\NodeableService
     */
    public function nodeableService()
    {
        return $this->nodeable_service = $this->nodeable_service ?? (new NodeableService($this));
    }

    /**
     * {@inheritdoc}
     *
     * @param \Illuminate\Database\Query\Builder
     * @return \Girover\Tree\Database\TreeQueryBuilder
     */
    public function newEloquentBuilder($query)
    {
        return (new NodeEloquentBuilder($query))->leftJoin('tree_nodes', 'tree_nodes.nodeable_id', (new static)->getTable().'.'.$this->getKeyName());
    }

    /**
     * {@inheritdoc}
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    // public function newCollection(array $models = [])
    // {
    //     return new NodeCollection($models);
    // }

    public static function bootNodeable()
    {
        static::saving(function ($model) {
            // dd($model);
        });

        // When model is deleted
        static::deleted(function ($node) {

            // if on_delete_shift_children property is false
            // then delete all children of the deleted node, otherwise delete only the node
            if ($node->on_delete_shift_children === false) {
                return $node->deleteChildren();
            }

            return $node->moveChildrenTo($node->father());
        });
    }

    /**
     * This method is called when new instance of Node is initialized
     *
     * @return void
     */
    public function initializeNodeable()
    {
        // Adding mass assignment fields to fillable array
        //$this->fillable = array_merge($this->fillable, static::$fillable_cols);
    }

    /**
     * Local scop to ignore getting divorced wives
     * 
     * @return \Girover\Tree\Database\Eloquent\NodeEloquentBuilder
     */
    public function scopeIgnoreDivorced($query)
    {
        return $query->where('divorced', false);
    }

    /**
     * To check if the nodeable model is connected with a node
     * Here we do not make request to database to find the node
     * 
     * @return bool
     */
    public function isNode()
    {
        if(! $this->exists)
        {
            return false;
        }

        return ($this->node_id && $this->nodeable_id && $this->treeable_id) ? true : false;
    }

    /**
     * To check if the nodeable model is connected with a node
     * Here we make request to database to find the node
     * 
     * @return bool
     */
    public function isAttached()
    {
        return $this->node ? true : false;
    }

    /**
     * To check if the nodeable model is connected with a node
     * Here we make request to database to find the node
     * 
     * @return bool
     */
    public function isNodeQuietly()
    {
        return ($this->node_id && $this->nodeable_id) ? true : false;
    }

    /**
     * Get partners of the node
     * if true is provided, get divorced partners with
     * if false is provided, don't get divorced partners with
     *
     * @param bool $with_divorced
     * @return \Illuminate\Database\Eloquent\Collection
     * 
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    public function partners()
    {
        // get wives
        if ($this->isMale()) {
            return  $this->wives()->get();
        }        
        // get husbands
        return $this->husband()->get();
    }

    /**
     * Get the tree that this node belongs.
     *
     * @param Illuminate\Database\Eloquent\Model
     * @return \Girover\Tree\Models\Tree
     */
    public function getTree()
    {
        if (!$this->isNode()) {
            throw new TreeException("This model is not connected with a node yet!", 1);
        }

        return (treeableModel())::find($this->treeable_id);
    }

    /**
     * Relationship for Getting wives of the node.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function wives()
    {
        return  $this->belongsToMany(get_class(), 'marriages', 'nodeable_husband_id', 'nodeable_wife_id')
                     ->withPivot('nodeable_husband_id', 'nodeable_wife_id', 'divorced');
    }

    /**
     * Get husband of the node
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsToMany
     * 
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    public function husband()
    {
        return $this->belongsToMany(get_class(), 'marriages', 'nodeable_wife_id', 'nodeable_husband_id')
                    ->withPivot('nodeable_husband_id', 'nodeable_wife_id', 'divorced');
    }

    /**
     * assign a wife to this node
     *
     * @param \Illuminate\Database\Eloquent\Model $wife
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function getMarriedWith(self $wife)
    {
        return $this->nodeableService()->getMarriedWith($wife);
    }

    /**
     * Check if nodeable is married with the given nodeable
     * @param Illuminate\Database\Eloquent\Model
     * 
     * @return bool 
     * @throws Girover\Tree\Exceptions\TreeException
     */
    public function isMarriedWith(self $wife)
    {
        return $this->nodeableService()->isMarriedWith($wife);
    }

    /**
     * Check if nodeable is married with the given nodeable
     * @param Illuminate\Database\Eloquent\Model
     * 
     * @return bool 
     * @throws Girover\Tree\Exceptions\TreeException
     */
    public function hasWife(self $wife)
    {
        return $this->nodeableService()->isMarriedWith($wife);
    }

    /**
     * Divorce
     *
     * @param \Illuminate\Database\Eloquent\Model $node
     * @return int
     */
    public function divorce(self $wife)
    {
        // only women will be divorced
        if ($wife->isMale()) {
            throw new TreeException("Provide node is not a woman to be divorced from a man", 1);
        }

        // only men are allowed to divorce
        if ($this->isFemale()) {
            throw new TreeException("This is a woman, however only men are allowed to divorce", 1);
        }
        return $this->wives()
                    ->where('nodeable_wife_id', $wife->getKey())
                    ->where('nodeable_husband_id', $this->getKey())
                    ->update(['divorced'=> true]);
    }

    
    /**
     * Set photo for this nodeable model
     * 
     * @param string $photo_name: the name of the photo without directory path.
     * 
     * @return bool
     */
    public function setPhoto(string $photo_name)
    {
        $this->photo = $photo_name;

        return $this->save();
    }

    /**
     * Determine if the node is Root in the tree
     *
     * @return bool
     */
    public function isRoot()
    {
        return $this->node_parent_id 
                     ? false 
                     : (($this->node_id and $this->nodeable_id and $this->treeable_id)
                        ?true
                        :false);
    }

    /**
     * Determine if the node has parent in the tree
     *
     * @return bool
     */
    public function hasParent()
    {
        return $this->node_parent_id ? true : false;
    }

    /**
     * Determine if the node has children
     *
     * @return bool
     */
    public function hasChildren()
    {
        return (bool)$this->childrenQuery()->count();
    }

    /**
     * Determine if the node has siblings
     *
     * @return bool
     */
    public function hasSiblings()
    {
        return (bool)$this->siblingsQuery()->count();
    }

    /**
     * Determine if the node has brothers
     *
     * @return bool
     */
    public function hasBrothers()
    {
        return (bool)$this->siblingsQuery()->male()->count();
    }

    /**
     * Determine if the node has brothers
     *
     * @return bool
     */
    public function hasSisters()
    {
        return (bool)$this->siblingsQuery()->female()->count();
    }

    /**
     * which generation this node belongs to
     *
     * @return int | NULL
     */
    public function generation()
    {
        $this->nodeableService()->throwExceptionIfNotNode();

        return DB::select(NodeQuerySql::generationSql($this))[0]->generation;
    }

    /**
     * we get all nodes in specific generation
     * considering that this node is the first generation
     *
     * @param int $generation_number
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function generationNodes($generation_number)
    {
        $this->nodeableService()->throwExceptionIfNotNode();

        $nodes = get_class()::fromQuery(NodeQuerySql::subtreeGenerationNodes($this, $generation_number));
        $nodes->load('wives');
        
        return $nodes;
    }

    /**
     * Get the Root of this node
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function root()
    {
        return static::tree($this->treeable_id)
                     ->whereNull('node_parent_id')
                     ->first();
    }

    /** UPDATED
     * Get the father of this node
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    public function father()
    {
        // can not get father of the root
        if ($this->isRoot()) {
            throw new TreeException("This model is Root, so it has no father.", 1);            
        }

        return static::tree($this->treeable_id)
                     ->where('node_id', $this->node_parent_id)
                     ->first();
    }

    /**
     * Get grandfather of this node
     *
     * @param \Girover\Tree\Models\Node
     */
    public function grandfather()
    {
        $this->nodeableService()->throwExceptionIfNotNode();

        return $this->ancestor(2);
    }

    /**
     * Get the ancestor with given number of this node
     * if NULL given get father
     *
     * @param \Illuminate\Database\Eloquent\Model
     */
    public function ancestor($ancestor = null)
    {
        if ($ancestor === null) {
            return $this->father();
        }

        return get_class()::fromQuery(NodeQuerySql::ancestor($this, $ancestor))->first();
    }

    /**
     * Getting all ancestors nodes from the location where the Pointer indicates to
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function ancestors()
    {
        return get_class()::fromQuery(NodeQuerySql::ancestors($this));
    }

    /**
     * make a query start for getting fathers siblings
     *
     * @return \Girover\Tree\Database\Eloquent\NodeEloquentBuilder
     */
    protected function siblingsOfFatherQuery()
    {
        $father = $this->father();
        return static::tree($this->treeable_id)
                     ->parentId($father->node_parent_id)
                     ->where('node_id', '<>', $father->node_id);
    }

    /**
     * Get all uncles and aunts of this node.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function siblingsOfFather()
    {
        return $this->siblingsOfFatherQuery()
                    ->get();
    }

    /**
     * Get all uncles of this node
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function uncles()
    {
        return $this->siblingsOfFatherQuery()->male()->get();
    }

    /**
     * Get all aunts of this node
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function aunts()
    {
        return $this->siblingsOfFatherQuery()->female()->get();
    }

    /**
     * make a query start for getting children
     *
     * @return \Girover\Tree\Database\Eloquent\NodeEloquentBuilder
     */
    protected function childrenQuery()
    {
        return static::tree($this->treeable_id)
                    ->parentId($this->node_id);
    }

    /**
     * Get all children of this node.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function children()
    {
        return $this->childrenQuery()
                    ->orderBy(sortColumn())
                    ->get();
    }

    /**
     * Get all direct sons of this node
     * without descendants
     *
     * @param \Illuminate\Database\Eloquent\Collection
     */
    public function sons()
    {
        return $this->childrenQuery()
                    ->orderBy(sortColumn())
                    ->male()
                    ->get();
    }

    /**
     * Get all direct daughters of this node
     * without descendants
     *
     * @param \Illuminate\Database\Eloquent\Collection
     */
    public function daughters()
    {
        return $this->childrenQuery()
                    ->orderBy(sortColumn())
                    ->female()
                    ->get();
    }

    /**
     * Count children of this node by gender.
     *
     * @return int
     */
    public function countChildren()
    {
        return $this->childrenQuery()->count();
    }

    /**
     * Count sons of this node.
     *
     * @return int
     */
    public function countSons()
    {
        return $this->childrenQuery()->male()->count();
    }

    /**
     * Count daughters of this node.
     *
     * @return int
     */
    public function countDaughters()
    {
        return $this->childrenQuery()->female()->count();
    }

    /**
     * Count siblings of this node.
     *
     * @return int
     */
    protected function countAllSiblings()
    {
        return $this->siblingsQuery()->count();
    }

    /**
     * to get siblings of the node that has the given gender
     * 
     * @param string $gender 'm'|'f'
     * @return int
     */
    protected function countSiblingsByGender($gender)
    {
        $this->nodeableService()->validateGender($gender);

        return $this->siblingsQuery()
                    ->where(gender(), $gender)
                    ->count();
    }

    /**
     * Count siblings of this node by gender.
     *
     * @return int
     */
    public function countSiblings()
    {
        return $this->countAllSiblings();
    }

    /**
     * count brothers of this node.
     *
     * @return int
     */
    public function countBrothers()
    {
        return $this->countSiblingsByGender(male());
    }

    /**
     * count sisters of the node.
     *
     * @return int
     */
    public function countSisters()
    {
        return $this->countSiblingsByGender(female());
    }

    /**
     * getting all descendants fo the node
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function descendants()
    {
        return get_class()::fromQuery(NodeQuerySql::descendants($this));
    }

    /**
     * getting all male descendants fo the node
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function maleDescendants()
    {
        return get_class()::fromQuery(NodeQuerySql::descendantsMale($this));
    }

    /**
     * getting all female descendants fo the node
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function femaleDescendants()
    {
        return get_class()::fromQuery(NodeQuerySql::descendantsFemale($this));
    }

    /**
     * Count all male descendants of this node.
     *
     * @return int
     */
    public function countDescendants()
    {
        // Here we subtract 1 from the total to not calculate the root
        return DB::select(NodeQuerySql::subtreeCountNodes($this))[0]->total_nodes - 1;
    }

    /**
     * Count all male descendants of this node.
     *
     * @return int
     */
    public function countMaleDescendants()
    {
        // Here we subtract 1 from the total to not calculate the root
        return DB::select(NodeQuerySql::subtreeCountMaleNodes($this))[0]->total_nodes - 1;
    }

    /**
     * Count all female descendants oc this node.
     *
     * @return int
     */
    public function countFemaleDescendants()
    {
        //Here we don't subtract 1 from the total because the root of subtree can't be female
        return DB::select(NodeQuerySql::subtreeCountFemaleNodes($this))[0]->total_nodes;
    }

    /**
     * Get the first child of this node
     *
     * @param \Girover\Tree\Models\Node
     */
    public function firstChild()
    {
        return  $this->childrenQuery()
                     ->orderBy(sortColumn(), 'ASC')
                     ->first();
        // return (new EagerTree($this->tree_id))->pointer->silentlyTo($this)->firstChild();
    }

    /**
     * Get the last child of this node
     *
     * @param \Girover\Tree\Models\Node
     */
    public function lastChild()
    {
        return  $this->childrenQuery()
                     ->orderBy(sortColumn(), 'DESC')
                     ->first();
    }

    /**
     * To get child that has the given order in the family
     * 
     * @param int $order
     * 
     * @return \Illuminate\Database\Eloquent\Model|null 
     */
    public function child(int $order)
    {
        $children = $this->children();

        if ($order < 1 || $order > $children->count()) {
            return null;
        }

        return $children[$order - 1];
    }

    /**
     * make a query for getting siblings
     *
     * @param string $gender m|f
     * @return \Girover\Tree\Database\Eloquent\NodeEloquentBuilder
     */
    protected function siblingsQuery()
    {
        return static::tree($this->treeable_id)
                     ->parentId($this->node_parent_id)
                     ->whereNot('node_id', $this->node_id);
    }

    /**
     * getting all sibling of the node
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function siblings()
    {
        return $this->siblingsQuery()
                    ->orderBy(sortColumn())
                    ->get();
    }

    /**
     * getting all sibling of the node including the node itself
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function withSiblings()
    {
        return static::tree($this->treeable_id)
                     ->parentId($this->node_parent_id)
                     ->orderBy(sortColumn())
                     ->get();
    }

    /**
     * getting all brothers of the node
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function brothers()
    {
        return $this->siblingsQuery()
                    ->male()
                    ->orderBy(sortColumn())
                    ->get();
    }

    /**
     * getting all brothers of the node
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function sisters()
    {
        return $this->siblingsQuery()
                    ->female()
                    ->orderBy(sortColumn())
                    ->get();
    }

    /**
     * Get all siblings of this node
     * including the node
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function siblingsAndSelf()
    {
        return $this->withSiblings();
    }

    /**
     * Get the next sibling that is younger than this node
     *
     * @return \Girover\Tree\Models\Node
     */
    public function nextSibling()
    {
        return $this->siblingsQuery()
                    ->where(sortColumn(), '>', $this->{sortColumn()})
                    ->orderBy(sortColumn())
                    ->first();
    }

    /**
     * Get all siblings who are younger than this node
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function nextSiblings()
    {
        return $this->siblingsQuery()
                    ->where(sortColumn(), '>', $this->{sortColumn()})
                    ->orderBy(sortColumn())
                    ->get();
    }

    /**
     * Get the next brother that is younger than this node
     *
     * @return \Girover\Tree\Models\Node
     */
    public function nextBrother()
    {
        return $this->siblingsQuery()
                    ->where(sortColumn(), '>', $this->{sortColumn()})
                    ->male()
                    ->orderBy(sortColumn())
                    ->first();
    }

    /**
     * Get the all next brother who are younger than this node
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function nextBrothers()
    {
        return $this->siblingsQuery()
                    ->where(sortColumn(), '>', $this->{sortColumn()})
                    ->male()
                    ->orderBy(sortColumn())
                    ->get();
    }

    /**
     * Get the next sister that is younger than this node
     *
     * @return \Girover\Tree\Models\Node
     */
    public function nextSister()
    {
        return $this->siblingsQuery()
                    ->where(sortColumn(), '>', $this->{sortColumn()})
                    ->female()
                    ->orderBy(sortColumn())
                    ->first();
    }

    /**
     * Get the all next sisters who are younger than this node
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function nextSisters()
    {
        return $this->siblingsQuery()
                    ->where(sortColumn(), '>', $this->{sortColumn()})
                    ->female()
                    ->orderBy(sortColumn())
                    ->get();
    }

    /**
     * Get the previous sibling og this node.
     *
     * @return \Girover\Tree\Models\Node
     */
    public function prevSibling()
    {
        return $this->siblingsQuery()
                    ->where(sortColumn(), '<', $this->{sortColumn()})
                    ->orderBy(sortColumn(), 'desc')
                    ->first();
    }

    /**
     * Get all siblings those are older than this node
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function prevSiblings()
    {
        return $this->siblingsQuery()
                    ->where(sortColumn(), '<', $this->{sortColumn()})
                    ->orderBy(sortColumn())
                    ->get();
    }

    /**
     * Get the previous brother that is older than this node
     *
     * @return \Girover\Tree\Models\Node
     */
    public function prevBrother()
    {
        return $this->siblingsQuery()
                    ->where(sortColumn(), '<', $this->{sortColumn()})
                    ->male()
                    ->orderBy(sortColumn(), 'desc')
                    ->first();
    }

    /**
     * Get the all previous brothers who are older than this node
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function prevBrothers()
    {
        return $this->siblingsQuery()
                    ->where(sortColumn(), '<', $this->{sortColumn()})
                    ->male()
                    ->orderBy(sortColumn())
                    ->get();
    }

    /**
     * Get the previous sister that is older than this node
     *
     * @return \Girover\Tree\Models\Node 
     */
    public function prevSister()
    {
        return $this->siblingsQuery()
                    ->where(sortColumn(), '<', $this->{sortColumn()})
                    ->female()
                    ->orderBy(sortColumn(), 'desc')
                    ->first();
    }

    /**
     * Get the all previous sisters who are older than this node
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function prevSisters()
    {
        return $this->siblingsQuery()
                    ->where(sortColumn(), '<', $this->{sortColumn()})
                    ->female()
                    ->orderBy(sortColumn())
                    ->get();
    }

    /**
     * Get the first sibling of node that the Pointer is indicating to
     *
     * @return \Girover\Tree\Models\Node
     */
    public function firstSibling()
    {
        return  $this->siblingsQuery()
                     ->orderBy(sortColumn())
                     ->first();
    }

    /**
     * Get last sibling of node that the Pointer is indicating to
     *
     * @return \Girover\Tree\Models\Node
     */
    public function lastSibling()
    {
        return  $this->siblingsQuery()
                     ->orderBy(sortColumn(), 'desc')
                     ->first();
    }

    /**
     * Get the first brother of node that the Pointer is indicating to
     *
     * @return \Girover\Tree\Models\Node
     */
    public function firstBrother()
    {
        return  $this->siblingsQuery()
                     ->male()
                     ->orderBy(sortColumn())
                     ->first();
    }

    /**
     * Get last brother of node that the Pointer is indicating to
     *
     * @return \Girover\Tree\Models\Node
     */
    public function lastBrother()
    {
        return  $this->siblingsQuery()
                     ->male()
                     ->orderBy(sortColumn(), 'desc')
                     ->first();
    }

    /**
     * Get the first sister of node that the Pointer is indicating to
     *
     * @return \Girover\Tree\Models\Node
     */
    public function firstSister()
    {
        return  $this->siblingsQuery()
                     ->female()
                     ->orderBy(sortColumn())
                     ->first();
    }

    /**
     * Get last sister of node that the Pointer is indicating to
     *
     * @return \Girover\Tree\Models\Node
     */
    public function lastSister()
    {
        return  $this->siblingsQuery()
                     ->female()
                     ->orderBy(sortColumn(), 'desc')
                     ->first();
    }

    /**
     * Create new sibling for this node
     * by default the new node is son.
     *
     * @param array|static data for the new sibling
     * @param string gender of the new sibling
     * @return \Girover\Tree\Models\Node|null
     */
    public function newSibling($data, $gender = '')
    {
        return $this->nodeableService()->newSibling($data, $gender);
    }

    /**
     * Create new brother for this node
     *
     * @param array|static data for the new sibling
     * @return \Girover\Tree\Models\Node|null
     */
    public function newBrother($data)
    {
        return $this->newSibling($data, male());
    }

    /**
     * Create new sister for this node
     * depending on the gender of 0 passed to `createNew` method
     *
     * @param array|static data for the new sister
     * @return \Girover\Tree\Models\Node|null
     */
    public function newSister($data)
    {
        return $this->newSibling($data, female());
    }

    /**
     * Create new child for this node
     *
     * @param array|static data for the new child
     * @param string $gender 'm'|'f'
     * @return \Girover\Tree\Models\Node|null
     */
    public function newChild($data, $gender = '')
    {
        if ($this->isFemale()) {
            throw new TreeException("No child can be created for female nodes", 1);
        }

        return $this->nodeableService()->newChild(...func_get_args());
    }

    /**
     * Create new son for this node
     *
     * @param array|static data for the new son
     * @return \Girover\Tree\Models\Node|null
     */
    public function newSon($data)
    {
        return $this->newChild($data, male());
    }

    /**
     * Create new son for this node
     *
     * @param array|static data for the new daughter
     * @return \Girover\Tree\Models\Node|null
     */
    public function newDaughter($data)
    {
        return $this->newChild($data, female());
    }

    /**
     * Move the node with its children
     * to be child of the given location
     * Both nodes should belong to same tree
     *
     * @param \Illuminate\Database\Eloquent\Model|string $location: location or node to move node to it
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    public function moveTo($location)
    { 
        return $this->nodeableService()->moveTo($location);
    }

    /**
     * Move all children of the node to be children of
     * the given node or the node that has the given location.
     *
     * @param \Girover\Tree\Models\Node|string $location
     * @return \Girover\Tree\Models\Node
     */
    public function moveChildrenTo($location = null)
    {
        $this->nodeableService()->moveChildrenTo($location);
    }

    /**
     * Create father for the root node in the tree
     *
     * @param array $data data for the new father
     * @return mixed
     */
    public function createFather($data)
    {
        return $this->nodeableService()->createFather($data);
    }

    /**
     * Save new node as root in the tree
     *
     * @param
     */
    public function saveAsRoot()
    {
        $this->location = Location::firstPossibleSegment();

        return $this->save();
    }

    /**
     * Save new node as root in the tree
     *
     * @return \Girover\Tree\Models\Node|false
     */
    public function makeAsMainNode()
    {
        return $this->getTree()->setMainNode($this);
    }

    /**
     * Determine if the node is an ancestor of the given node.
     *
     * @param \Illuminate\Database\Eloquent\Model
     * @return bool
     */
    public function isAncestorOf($node)
    {
        $ancestors = $node->ancestors();

        return (bool)$ancestors->where('nodeable_id', $this->nodeable_id)->count();
    }

    /**
     * Determine if the node is the father of the given node.
     *
     * @param \Girover\Tree\Models\Node
     * @return bool
     */
    public function isFatherOf($node)
    {
        return (bool)($node->node_parent_id == $this->node_id);
    }

    /**
     * Determine if the node is the child of the given node.
     *
     * @param \Girover\Tree\Models\Node
     * @return bool
     */
    public function isChildOf($node)
    {
        return (bool)($this->node_parent_id == $node->node_id);
    }

    /**
     * Determine if the node is the child of the given node.
     *
     * @return bool
     */
    public function isSiblingOf($node)
    {
        return (bool)($this->node_parent_id == $node->node_parent_id);
    }

    /**
     * Determine if the node is the main node in its tree.
     *
     * @return bool
     */
    public function isMainNode()
    {
        $main_node = $this->getTree()->mainNode();

        return ($main_node) ? $this->location===$main_node->location : false;
    }

    /**
     * Determine if the node is a male node.
     *
     * @return bool
     */
    public function isMale()
    {
        return ($this->{gender()} === male()) ? true : false;
    }

    /**
     * Determine if the node is a female node.
     *
     * @return bool
     */
    public function isFemale()
    {
        return ($this->{gender()} === female()) ? true : false;
    }

    /**
     * Delete all children of this node
     *
     * @return int
     */
    public function deleteChildren()
    {
        return static::tree($this->treeable_id)
                     ->locationNot($this->location)
                     ->where('location', 'like', $this->location.'%')
                     ->delete();
    }

    /**
     * To make sure that when a node will be deleted
     * so its children will not
     * but they will move to be children of the father
     * of the deleted node
     * 
     * @return \Girover\Tree\Models\Node
     */
    public function onDeleteMoveChildren()
    {
        $this->on_delete_shift_children = true;

        return $this;
    }

    /**
     * To change the location of a node
     * to be after the given node
     * 
     * @param \Girover\Tree\Models\Node $node
     * @throws \Girover\Tree\Exceptions\TreeException
     */
    public function moveAfter($node)
    {
        // Move the node after its sibling
        NodeRelocator::moveAfter($this, $node);
    }

    /**
     * To change the location of a node
     * to be after the given node
     * 
     * @param \Girover\Tree\Models\Node $node
     */
    public function moveBefore($node)
    {
        // Move the node after its sibling
        NodeRelocator::moveBefore($this, $node);  
    }

    /**
     * Getting the node that this nodeable is connected with
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function node()
    {
        return $this->hasOne(TreeNode::class, 'nodeable_id', $this->getKeyName());
    }

    /**
     * getting the url of the photo of this node
     * 
     * @return string
     */
    public function photoURL()
    {
        return photoAsset($this);
    }

    /**
     * To detach a node from a tree
     * it will delete the node from nodes table,
     * but nodeable will still exist.
     * 
     * @return bool
     */
    public function detachFromTree()
    {
        if(!$this->node())
            throw new TreeException("This nodeable model is not attached to any node in any tree.", 1);
        
        $this->nodeableService()->detachFromTree();
    }

    /**
     * Generate Tree Html code from this node
     *
     * @return string html code for the tree from this node
     */
    public function buildTree()
    {
        return $this->nodeableService()->buildTreeFromANode();
    }

    /**
     * Generate Tree Html code from this node
     *
     * @return string html code for the tree from this node
     */
    public function toHtml()
    {
        return $this->buildTree();
    }

    /**
     * Generate Tree Html code from this node
     *
     * @return string html code for the tree from this node
     */
    public function build()
    {
        return $this->buildTree();
    }

    /**
     * Rendering the generate Tree Html code from this node
     *
     * @return string html code for the tree from this node
     */
    public function render()
    {
        return $this->buildTree();
    }
}
