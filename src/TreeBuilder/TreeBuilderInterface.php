<?php

namespace Girover\Tree\TreeBuilder;

interface TreeBuilderInterface{

    /**
     * to get empty tree as Html to render in the browser
     * 
     * @return string
     */
    public function emptyTree();

    /**
     * Getting the tree as Html string to render in the browser
     * 
     * @return string
     */
    public function render();
}