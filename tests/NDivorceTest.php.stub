<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NDivorceTest extends TestCase
{
    use DatabaseTransactions, Factoryable;

    /**
     * -------------------------------------------
     * testing divorce method
     * --------------------------------------------
     */
    /** @test */
    public function it_can_divorce_a_node()
    {
        $tree = $this->createTreeable();
        $root = $tree->createRoot($this->makeNodeable());
        // create new node in database table
        $man   = $root->newSon($this->makeNodeable());
        $woman   = $root->newDaughter($this->makeNodeable());

        $man->getMarriedWith($woman);
        $this->assertDatabaseHas('marriages', ['nodeable_husband_id'=>$man->id, 'nodeable_wife_id'=>$woman->id, 'divorced'=>false]);

        $man->divorce($woman);
        $this->assertDatabaseHas('marriages', ['nodeable_husband_id'=>$man->id, 'nodeable_wife_id'=>$woman->id, 'divorced'=>true]);
    }

    // /** @test */
    // public function it_throws_TreeException_if_given_param_is_not_node()
    // {
    //     $this->expectException(TreeException::class);
    //     // create new node in database table
    //     $man   = $this->createMaleNode();

    //     $man->divorce('not node');
    // }

    // /** @test */
    // public function it_throws_TreeException_if_female_node_tries_to_divorce_node()
    // {
    //     $this->expectException(TreeException::class);
    //     // create new node in database table
    //     $woman    = $this->createFemaleNode();
    //     // creating another male node
    //     $man   = $this->createMaleNode(['location'=>'aa.ww']);

    //     $woman->divorce($man);
    // }

    // /** @test */
    // public function it_throws_TreeException_if_given_node_is_male_node()
    // {
    //     $this->expectException(TreeException::class);
    //     // create new node in database table
    //     $man    = $this->createMaleNode();
    //     // creating another male node
    //     $another_man   = $this->createMaleNode(['location'=>'aa.ww']);

    //     $man->divorce($another_man);
    // }
}