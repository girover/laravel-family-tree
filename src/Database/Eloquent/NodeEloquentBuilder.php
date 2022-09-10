<?php

namespace Girover\Tree\Database\Eloquent;

use Illuminate\Database\Eloquent\Builder;

class NodeEloquentBuilder extends Builder
{
    /**
     * to add constraint [models that belongs the given tree number]
     * to QueryBuilder
     * to achieve where tree_id = $tree_id on QueryBuilder
     * @param int $treeable_id
     * @return \Girover\Tree\Database\Eloquent\NodeEloquentBuilder
     */
    public function tree($treeable_id)
    {
        return $this->where('treeable_id', $treeable_id);
    }

    /**
     * to add constraint [models have gender of m] to QueryBuilder
     * @return \Girover\Tree\Database\Eloquent\NodeEloquentBuilder
     */
    public function male()
    {
        $this->where(gender(), male());

        return $this;
    }

    /**
     * to add constraint [models have gender of f] to QueryBuilder
     * @return \Girover\Tree\Database\Eloquent\NodeEloquentBuilder
     */
    public function female()
    {
        $this->where(gender(), female());

        return $this;
    }

    /**
     *
     */
    public function root()
    {
        return $this->first();
    }

    /**
     * 
     */
    public function parentId($parent_id)
    {
        return $this->where('node_parent_id', $parent_id);
    }

    /**
     * Get the total number of nodes in the tree
     *
     * @return int count of nodes in the tree
     */
    public function total()
    {
        return $this->count();
    }

    /**
     * Get the total number of nodes in the tree
     *
     * @return int count of nodes in the tree
     */
    public function totalNodes()
    {
        return $this->total();
    }

    /**
     * Get how many generations there are in the tree
     * @param int $generation
     * @return \Girover\Tree\Database\Eloquent\NodeEloquentBuilder
     */
    public function generation($generation)
    {
        // return $this->where('location', 'REGEXP', Location::singleGenerationREGEXP($generation));
    }

    /**
     * Get the data of the tree as html
     *
     * @return string tree as html
     */
    public function toTree()
    {
        return $this->get()->toTree();
    }

    /**
     * Get the data of the tree as html
     *
     * @return string html string
     */
    public function toHtml()
    {
        return $this->toTree();
    }

    /**
     * Get the data of the tree as html
     *
     * @return string html string
     */
    public function draw()
    {
        return $this->toTree();
    }
}
