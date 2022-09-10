<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NGrandfatherTest extends TestCase
{
    use DatabaseTransactions, Factoryable;

    /**
     * -------------------------------------------
     * testing grandfather method
     * --------------------------------------------
     */
    /** @test */
    public function it_can_get_grandfather_of_node()
    {
        $tree = $this->createTreeable();
        // create new node in database table
        $nodeable = $this->createNodeable();
        $root = $tree->createRoot($nodeable);
        
        $nodeable = $this->createNodeable();
        $son  = $root->newSon($nodeable);
        
        $nodeable = $this->createNodeable();
        $grand_son = $son->newSon($nodeable);

        $grand_father = $grand_son->grandFather();
        
        $this->assertTrue($root->getKey() === $grand_father->getKey());
    }
}