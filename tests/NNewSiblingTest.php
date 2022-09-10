<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NNewSiblingTest extends TestCase
{
    use DatabaseTransactions, Factoryable;


    /**
     * -------------------------------------------
     * testing newSibling method
     * --------------------------------------------
     */
    /** @test */
    public function it_can_create_new_sibling_for_node()
    {
        $tree = $this->createTreeable();
        
        $root    = $tree->createRoot($this->makeMaleNodeable()->toArray());        
        $node    = $root->newSon($this->makeNodeable()->toArray());        
        $sibling = $node->newSibling($this->makeNodeable()->toArray(), male());

        $this->assertDatabaseHas('nodeables', ['name'=>$root->name]);
        $this->assertDatabaseHas('nodeables', ['name'=>$node->name]);
        $this->assertDatabaseHas('nodeables', ['name'=>$sibling->name]);
        $this->assertDatabaseHas('tree_nodes', ['nodeable_id'=>$root->getKey()]);
        $this->assertDatabaseHas('tree_nodes', ['nodeable_id'=>$node->getKey()]);
        $this->assertDatabaseHas('tree_nodes', ['nodeable_id'=>$sibling->getKey()]);
        $this->assertTrue($root->node_id == $node->node_parent_id);
        $this->assertTrue($root->node_id == $sibling->node_parent_id);
    }

    /** @test */
    public function it_will_create_new_male_sibling_when_no_gender_provided()
    {
        $tree = $this->createTreeable();
        
        $root    = $tree->createRoot($this->makeMaleNodeable()->toArray());        
        $node    = $root->newSon($this->makeNodeable()->toArray());        
        $sibling = $node->newSibling($this->makeNodeable()->toArray());

        $this->assertDatabaseHas('nodeables', ['name'=>$root->name]);        
        $this->assertDatabaseHas('nodeables', ['name'=>$node->name]);        
        $this->assertDatabaseHas('nodeables', ['name'=>$sibling->name]);  
        $this->assertDatabaseHas('tree_nodes', ['nodeable_id'=>$root->getKey()]);
        $this->assertDatabaseHas('tree_nodes', ['nodeable_id'=>$node->getKey()]);
        $this->assertDatabaseHas('tree_nodes', ['nodeable_id'=>$sibling->getKey()]);      
        $this->assertTrue($root->treeable_id === $sibling->treeable_id);
        $this->assertTrue($root->node_id === $sibling->node_parent_id);
        $this->assertTrue($sibling->{gender()} == male());
    }

    /** @test */
    public function it_will_create_new_female_sibling_when_female_gender_provided()
    {
        $tree = $this->createTreeable();
        
        $root    = $tree->createRoot($this->makeMaleNodeable()->toArray());        
        $node    = $root->newSon($this->makeNodeable()->toArray());        
        $sibling = $node->newSibling($this->makeNodeable()->toArray(), female());

        $this->assertDatabaseHas('nodeables', ['name'=>$root->name]);        
        $this->assertDatabaseHas('nodeables', ['name'=>$node->name]);        
        $this->assertDatabaseHas('nodeables', ['name'=>$sibling->name]);  
        $this->assertDatabaseHas('tree_nodes', ['nodeable_id'=>$root->getKey()]);
        $this->assertDatabaseHas('tree_nodes', ['nodeable_id'=>$node->getKey()]);
        $this->assertDatabaseHas('tree_nodes', ['nodeable_id'=>$sibling->getKey()]);      
        $this->assertTrue($root->treeable_id === $sibling->treeable_id);
        $this->assertTrue($root->node_id === $sibling->node_parent_id);
        $this->assertTrue($sibling->{gender()} == female());
    }

    /** @test */
    public function it_can_not_create_sibling_for_node_if_wrong_gender_provided()
    {
        $this->expectException(TreeException::class);

        $tree  = $this->createTreeable();        
        $root  = $tree->createRoot($this->makeMaleNodeable()->toArray());        
        $node  = $root->newSon($this->makeNodeable()->toArray());
        
        $this->assertDatabaseHas('nodeables', ['name'=>$root->name]);        
        $this->assertDatabaseHas('nodeables', ['name'=>$node->name]);     
        $this->assertDatabaseHas('tree_nodes', ['nodeable_id'=>$root->getKey()]);
        $this->assertDatabaseHas('tree_nodes', ['nodeable_id'=>$node->getKey()]);      
        $this->assertTrue($root->treeable_id === $node->treeable_id);
        $this->assertTrue($root->node_id === $node->node_parent_id);

        // Must throw TreeException
        $node->newSibling($this->makeNodeable()->toArray(), 'wrong gender');
    }

    /** @test */
    public function it_can_not_create_new_sibling_for_node_if_no_data_are_provided()
    {
        $this->expectException(TreeException::class);
        
        $tree   = $this->createTreeable();        
        $root   = $tree->createRoot($this->makeMaleNodeable()->toArray());        
        $child  = $root->newSon($this->makeNodeable()->toArray());

        $this->assertDatabaseHas('nodeables', ['name'=>$root->name]);        
        $this->assertDatabaseHas('nodeables', ['name'=>$child->name]);     
        $this->assertDatabaseHas('tree_nodes', ['nodeable_id'=>$root->getKey()]);
        $this->assertDatabaseHas('tree_nodes', ['nodeable_id'=>$child->getKey()]);      
        $this->assertTrue($root->treeable_id === $child->treeable_id);
        $this->assertTrue($root->node_id === $child->node_parent_id);

        // Must throw TreeException
        $child->newSibling([]);
    }
}