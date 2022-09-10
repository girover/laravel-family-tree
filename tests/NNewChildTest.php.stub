<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NNewChildTest extends TestCase
{
    use DatabaseTransactions, Factoryable;


    /**
     * -------------------------------------------
     * testing newSibling method
     * --------------------------------------------
     */
    /** @test */
    public function it_can_create_new_child_for_node()
    {
        $tree = $this->createTreeable();
        
        $root = $tree->createRoot($this->makeNodeable()->toArray());
        
        $child  = $root->newChild($this->makeNodeable()->toArray(), 'm');
        
        $this->assertDatabaseHas('nodeables', ['name'=>$child->name]);        
        $this->assertTrue($root->treeable_id === $child->treeable_id);
        $this->assertTrue(Location::areFatherAndChild($root->location, $child->location));
    }

    /** @test */
    public function it_will_create_new_male_child_when_no_gender_provided()
    {
        $tree = $this->createTreeable();
        
        $root = $tree->createRoot($this->makeNodeable()->toArray());
        
        $child  = $root->newChild($this->makeNodeable()->toArray());

        $this->assertDatabaseHas('nodeables', ['name'=>$child->name]);        
        $this->assertTrue($root->treeable_id === $child->treeable_id);
        $this->assertTrue(Location::areFatherAndChild($root->location, $child->location));
        $this->assertTrue($child->isMale());
    }

    /** @test */
    public function it_will_create_new_female_child_when_female_gender_provided()
    {
        $tree = $this->createTreeable();
        
        $root = $tree->createRoot($this->makeNodeable()->toArray());
        
        $child  = $root->newChild($this->makeNodeable()->toArray(), 'f');
        
        $this->assertDatabaseHas('nodeables', ['name'=>$child->name]);        
        $this->assertTrue($root->treeable_id === $child->treeable_id);
        $this->assertTrue(Location::areFatherAndChild($root->location, $child->location));
        $this->assertTrue($child->isFemale());
    }

    /** @test */
    public function it_can_not_create_child_if_wrong_gender_provided()
    {
        $this->expectException(TreeException::class);

        $tree = $this->createTreeable();
        
        $root = $tree->createRoot($this->makeNodeable()->toArray());
        
        $root->newChild($this->makeNodeable()->toArray(), 'g');
    }

    /** @test */
    public function it_can_not_create_new_child_if_no_data_are_provided()
    {
        $this->expectException(TreeException::class);
        
        $tree = $this->createTreeable();
        
        $root = $tree->createRoot($this->makeNodeable()->toArray());

        $root->newChild([]);
    }
}