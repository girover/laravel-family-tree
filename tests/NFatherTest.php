<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NFatherTest extends TestCase
{
    use DatabaseTransactions, Factoryable;

    /**
     * -------------------------------------------
     * testing father method
     * --------------------------------------------
     */
    /** @test */
    public function it_can_get_father_of_node()
    {
        $tree = $this->createTreeable();
        // create new node in database table        
        $root = $tree->createRoot($this->makeMaleNodeable());        
        $son  = $root->newSon($this->makeNodeable()->toArray());        
        
        $father = $son->father();

        $this->assertDatabaseHas('nodeables', ['name'=>$root->name]);        
        $this->assertDatabaseHas('nodeables', ['name'=>$son->name]);             
        $this->assertDatabaseHas('tree_nodes', ['nodeable_id'=>$root->getKey()]);        
        $this->assertDatabaseHas('tree_nodes', ['nodeable_id'=>$son->getKey()]);
        $this->assertTrue($root->treeable_id === $son->treeable_id);
        $this->assertTrue($son->node_parent_id == $root->node_id);
        $this->assertTrue($father->node_id == $root->node_id);
        // $this->assertTrue($node->id === $father->id);
    }
    /** @test */
    public function it_throws_TreeException_when_trying_to_get_father_of_the_root()
    {
        $this->expectException(TreeException::class);

        $tree = $this->createTreeable();
        // create new node in database table
        $root = $tree->createRoot($this->makeMaleNodeable()->toArray());

        $this->assertDatabaseHas('nodeables', ['name'=>$root->name]);
        $this->assertDatabaseHas('tree_nodes', ['nodeable_id'=>$root->getKey()]); 
        // Must Throw TreeException
        $root->father();
    }
}