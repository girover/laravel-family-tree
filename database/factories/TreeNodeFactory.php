<?php

namespace Girover\Tree\Database\Factories;

use Girover\Tree\Location;
use Girover\Tree\Models\TreeNode;
use Illuminate\Database\Eloquent\Factories\Factory;


class TreeNodeFactory extends Factory
{
    protected $model = TreeNode::class;

    
    public function definition()
    {
        // $this->model =  ModelService::nodeModel();
        return [
            'nodeable_id' => 1,
            'treeable_id' => 1,
            'node_parent_id' => 1,
        ];
    }
}
