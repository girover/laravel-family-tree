<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NNewSisterTest extends TestCase
{
    use DatabaseTransactions, Factoryable;


    /**
     * -------------------------------------------
     * testing newSister method
     * --------------------------------------------
     */
    /** @test */
    public function it_can_create_new_sister_for_node()
    {
        $tree = $this->createTreeable();
        
        $root = $tree->createRoot($this->makeMaleNodeable()->toArray());
        
        $node  = $root->newSon($this->makeNodeable()->toArray());
        
        $sister = $node->newSister($this->makeNodeable()->toArray());

        $this->assertDatabaseHas('nodeables', ['name'=>$root->name]);        
        $this->assertDatabaseHas('nodeables', ['name'=>$node->name]);        
        $this->assertDatabaseHas('nodeables', ['name'=>$sister->name]); 

        $this->assertTrue($sister->isFemale());
        $this->assertTrue($root->treeable_id === $node->treeable_id);
        $this->assertTrue($root->treeable_id === $sister->treeable_id);
        
        $this->assertTrue($node->isSiblingOf($sister));
        $this->assertTrue($node->{gender()} == male());
        $this->assertTrue($sister->{gender()} == female());
    }

    /** @test */
    public function it_can_not_create_new_sister_for_root_node()
    {
        $this->expectException(TreeException::class);
        
        $tree = $this->createTreeable();
        
        $root = $tree->createRoot($this->makeMaleNodeable()->toArray());

        $this->assertDatabaseHas('nodeables', ['name'=>$root->name]);
        $this->assertDatabaseHas('tree_nodes', ['nodeable_id'=>$root->getKey()]);

        // This must throw exception
        $root->newSister($this->makeFemaleNodeable()->toArray());
    }

    /** @test */
    public function it_can_not_create_new_sister_for_node_if_no_data_are_provided()
    {
        $this->expectException(TreeException::class);
        
        $tree = $this->createTreeable();
        
        $root = $tree->createRoot($this->makeMaleNodeable()->toArray());

        $son = $root->newSon($this->makeNodeable()->toArray());

        $this->assertDatabaseHas('nodeables', ['name'=>$root->name]);
        $this->assertDatabaseHas('nodeables', ['name'=>$son->name]);
        $this->assertDatabaseHas('tree_nodes', ['nodeable_id'=>$root->getKey()]);
        $this->assertDatabaseHas('tree_nodes', ['nodeable_id'=>$son->getKey()]);
        $this->assertTrue($root->node_id == $son->node_parent_id);

        $son->newSister([]);
    }
}