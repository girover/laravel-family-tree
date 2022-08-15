<?php

namespace Girover\Tree\Services;

use \Illuminate\Database\Eloquent\Model;
use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
use Girover\Tree\TreeBuilder\HtmlTreeBuilder;

class TreeableService
{
    /**
     * @var \Illuminate\Database\Eloquent\Model the treeable model
     */
    public $treeable;
    
    /**
     * @var Girover\Tree\Services\NodeableService
     */
    protected $node_service;
    
    /**
     * Constructor of the class
     * @param \Illuminate\Database\Eloquent\Model $tree
     */
    public function __construct(Model $treeable) {
        $this->treeable = $treeable;
    }

    /**
     * getting instance of nodeable service class
     * 
     * @return Girover\Tree\Services\NodeableService
     */
    public function nodeableService($nodeable = null)
    {
        if (!$nodeable) {
            return $this->node_service ?? (new NodeableService());
        }

        return $this->node_service ?? (new NodeableService($nodeable));
    }    

    /**
     * Creating root for an empty tree
     * 
     * @param array| \Illuminate\Database\Eloquent\Model
     * 
     * @return bool
     * 
     * @throws Girover\Tree\Exceptions\TreeException
     */
    public function createRoot($data)
    {
        if (! $data instanceof (nodeableModel()) && !is_array($data)) {
            throw new TreeException("Bad argument: second argument must be of type: ".nodeableModel(). " OR an Array", 1);
        }
        if (empty($data)) {
            throw new TreeException("Error: no data are provided to create Root for the tree.", 1);
        }
        
        // The tree is not empty, e.i. There is already a Root for this tree.
        if (!$this->treeable->isEmptyTree()) {
            throw new TreeException("Root for tree is already exists", 1);
        }

        $location = Location::generateRootLocation();
        
        $empty_nodeable = new (nodeableModel());
        $empty_nodeable->treeable_id = $this->treeable->getKey();

        return $this->nodeableService($empty_nodeable)->createNewNode($data, $location,'m');
    }


    /**
     * Converting the tree to html to render in browser
     * 
     * @return string;
     */
    public function buildTree()
    {
        if (app()->has('TreeBuilderInterface')) {
            $tree_generator = app()->makeWith('TreeBuilderInterface', [$this->treeable]);
        }else{
            $tree_generator = new HtmlTreeBuilder($this->treeable);
        }

        return $tree_generator->draw();
    }
}