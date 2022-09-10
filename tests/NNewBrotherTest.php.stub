<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NNewBrotherTest extends TestCase
{
    use DatabaseTransactions, Factoryable;


    /**
     * -------------------------------------------
     * testing newBrother method
     * --------------------------------------------
     */

    /** @test */
    public function it_can_create_new_brother_for_node()
    {
        $tree = $this->createTreeable();
        
        $root = $tree->createRoot($this->makeNodeable()->toArray());
        
        $node  = $root->newSon($this->makeNodeable()->toArray());
        
        $brother = $node->newBrother($this->makeNodeable()->toArray());

        $this->assertDatabaseHas('nodeables', ['name'=>$brother->name]);        
        $this->assertTrue($root->treeable_id === $brother->treeable_id);
        $this->assertTrue(Location::areSiblings($node->location, $brother->location));
    }

    /** @test */
    public function it_can_not_create_new_brother_for_root_node()
    {
        $this->expectException(TreeException::class);
        // create new node in database table
        $tree = $this->createTreeable();
        
        $root = $tree->createRoot($this->makeNodeable()->toArray());

        $root->newBrother($this->makeNode()->toArray());
    }

    /** @test */
    public function it_can_not_create_new_brother_for_node_if_no_data_are_provided()
    {
        $this->expectException(TreeException::class);
        
        $tree = $this->createTreeable();
        
        $root = $tree->createRoot($this->makeNodeable()->toArray());

        $son = $root->newSon($this->makeNodeable()->toArray());

        $son->newBrother([]);
    }  
}