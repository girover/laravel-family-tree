<?php


namespace Girover\Tree\TreeBuilder;

use Illuminate\Database\Eloquent\Model;

class TreeBuilder
{
    /**
     * @var \Illuminate\Database\Eloquent\Model|null
     */
    protected $tree = null;
 
    /**
     * @var \Illuminate\Database\Eloquent\Collection|null
     */
    protected $nodes = null;

    /**
     * @var int uses in converting tree to html
     */
    public $nodesCount = 1;

    /**
     * @var callable uses to get custom css class name for specific nodeables.
     */
    protected $node_css_classes;

    /**
     * @var callable uses to get custom attributes for nodeables.
     */
    protected $node_html_attributes;

    /**
     * @var array Here we store callables from user to get custom Html strings.
     */
    protected $callables = [];

    /**
     * instantiate a tree generator
     * 
     * @param \Illuminate\Database\Eloquent\Model $tree
     * @return void
     */
    public function __construct(Model $tree) {

        $this->tree = $tree;

        // app()->has('nodeCssClasses') ? $this->callables['node_css_classes'] = app()->make('nodeCssClasses') : false;
        // app()->has('node_html_attributes') ? $this->callables['node_html_attributes'] = app()->make('node_html_attributes') : false;
        app()->has('tree_node_info') ? $this->callables['tree_node_info'] = app()->make('tree_node_info') : false;
    }
}