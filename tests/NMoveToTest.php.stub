<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Location;
use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NMoveToTest extends TestCase
{
    use DatabaseTransactions, Factoryable;

    /**
     * -------------------------------------------
     * testing moveTo method
     * --------------------------------------------
     */
    /** @test */
    public function it_can_move_node_to_be_child_of_another_node()
    {
        $tree = $this->createTreeable();
        $root = $tree->createRoot($this->makeNodeable()->toArray());

        $son1 = $root->newSon($this->makeNodeable()->toArray());
        $son2 = $root->newSon($this->makeNodeable()->toArray());

        $this->assertTrue($son1->treeable_id === $son2->treeable_id);
        $this->assertTrue(Location::areSiblings($son1->location, $son2->location));
        
        // Make son1 as son of son2
        $son1->moveTo($son2);

        $son2_son = $son2->firstChild();
        $this->assertNotNull($son2_son);       
    }
}