<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NNewDaughterTest extends TestCase
{
    use DatabaseTransactions, Factoryable;


    /**
     * -------------------------------------------
     * testing newBrother method
     * --------------------------------------------
     */

    /** @test */
    public function it_can_create_new_daughter_for_node()
    {
        $tree = $this->createTreeable();
        
        $root     = $tree->createRoot($this->makeMaleNodeable()->toArray());        
        $node     = $root->newSon($this->makeNodeable()->toArray());        
        $daughter = $root->newDaughter($this->makeNodeable()->toArray());

        $this->assertDatabaseHas('nodeables', ['name'=>$root->name]);        
        $this->assertDatabaseHas('nodeables', ['name'=>$node->name]);        
        $this->assertDatabaseHas('nodeables', ['name'=>$daughter->name]);        
        $this->assertDatabaseHas('tree_nodes', ['nodeable_id'=>$root->getKey()]);        
        $this->assertDatabaseHas('tree_nodes', ['nodeable_id'=>$node->getKey()]);        
        $this->assertDatabaseHas('tree_nodes', ['nodeable_id'=>$daughter->getKey()]);        
        $this->assertTrue($root->treeable_id === $daughter->treeable_id);
        $this->assertTrue($node->node_parent_id == $daughter->node_parent_id);
        $this->assertTrue($daughter->{gender()} == female());
    }

    /** @test */
    public function it_should_not_create_new_daughter_for_a_female_node()
    {
        $this->expectException(TreeException::class);
        // create new node in database table
        $tree = $this->createTreeable();
        
        $root = $tree->createRoot($this->makeMaleNodeable()->toArray());
        $female = $root->newDaughter($this->makeNodeable()->toArray());

        $this->assertDatabaseHas('nodeables', ['name'=>$root->name]);        
        $this->assertDatabaseHas('nodeables', ['name'=>$female->name]);       
        $this->assertDatabaseHas('tree_nodes', ['nodeable_id'=>$root->getKey()]);        
        $this->assertDatabaseHas('tree_nodes', ['nodeable_id'=>$female->getKey()]);        
        $this->assertTrue($root->treeable_id === $female->treeable_id);
        $this->assertTrue($female->{gender()} == female());
        // Must throw TreeException
        $female->newDaughter($this->makeNodeable()->toArray());
    }

    /** @test */
    public function it_can_not_create_new_daughter_for_node_if_no_data_are_provided()
    {
        $this->expectException(TreeException::class);
        
        $tree = $this->createTreeable();
        
        $root = $tree->createRoot($this->makeMaleNodeable()->toArray());

        $this->assertDatabaseHas('nodeables', ['name'=>$root->name]);         
        $this->assertDatabaseHas('tree_nodes', ['nodeable_id'=>$root->getKey()]);
        // Must throw TreeException
        $root->newDaughter([]);
    }  
}