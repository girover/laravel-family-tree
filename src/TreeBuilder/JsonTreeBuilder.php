<?php

namespace Girover\Tree\TreeBuilder;

use Girover\Tree\TreeBuilder\TreeBuilderInterface;

class JsonTreeBuilder implements TreeBuilderInterface
{
    public $nodes = [];

    public function emptyTree()
    {
        return json_encode($this->nodes);
    }

    public function render()
    {
        
    }
}
