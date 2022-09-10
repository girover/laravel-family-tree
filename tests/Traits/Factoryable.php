<?php

namespace Girover\Tree\Tests\Traits;

use Girover\Tree\Models\Treeable;
use Girover\Tree\Models\Nodeable;
use Girover\Tree\Models\TreeNode;

/**
 * 
 */
trait Factoryable
{
    public function createTreeable()
    { 
        return Treeable::factory()->create();
    }
    public function makeTreeable()
    {
        return Treeable::factory()->make();
    }
    public function createNodeable()
    { 
        return Nodeable::factory()->create();
    }
    public function makeNodeable()
    {
        return Nodeable::factory()->make();
    }
    public function createMaleNodeable()
    {
        return Nodeable::factory()->male()->create();
    }
    public function createFemaleNodeable($data = [])
    {
        return Nodeable::factory()->female()->create();
    }
    public function makeMaleNodeable()
    {
        return Nodeable::factory()->male()->make();
    }
    public function makeFemaleNodeable()
    {
        return Nodeable::factory()->female()->make();
    }
    public function createNode($data = [])
    {
        return TreeNode::factory()->create($data);
    }
    public function makeNode($data = [])
    {
        return TreeNode::factory()->make($data);
    }
    public function makeMaleNode()
    {
        return TreeNode::factory()->make(['gender'=>'m']);
    }
    public function makeFemaleNode()
    {
        return TreeNode::factory()->make(['gender'=>'f']);
    }
}
