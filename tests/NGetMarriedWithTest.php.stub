<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Location;
use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use TypeError;

class NGetMarriedWithTest extends TestCase
{
    use DatabaseTransactions, Factoryable;

    /**
     * -------------------------------------------
     * testing getMarriedWith($wife) method
     * --------------------------------------------
     */
    /** @test 1 */
    public function it_can_get_married_with_female_node()
    {
        $tree = $this->createTreeable();
        $root = $tree->createRoot($this->makeNodeable()->toArray());
        $man   = $root->newSon($this->makeNodeable()->toArray());
        $woman = $root->newDaughter($this->makeNodeable()->toArray());

        $man->getMarriedWith($woman);

        $this->assertDatabaseHas('marriages', ['nodeable_husband_id'=>$man->getKey(), 'nodeable_wife_id'=>$woman->getKey()]);
    }

    /** @test 2 */
    public function it_throws_TypeError_Exception_if_given_param_is_not_Nodeable()
    {
        $this->expectException(TypeError::class);
        // create new node in database table
        $man   = $this->createNodeable();
        $woman = 'string instead of Model object';

        // wrong date is provided
        $man->getMarriedWith($woman);
    }

    // /** @test 3 */
    public function it_throws_TreeException_if_given_param_is_male_Node()
    {
        $this->expectException(TreeException::class);
        // create new node in database table
        $tree   = $this->createTreeable();
        $root   = $tree->createRoot($this->makeNodeable()->toArray());
        $man    = $root->newSon($this->makeNodeable()->toArray());
        $man2   = $root->newSon($this->makeNodeable()->toArray());

        // wrong date is provided
        $man->getMarriedWith($man2);
    }

    /** @test 4 */
    public function it_throws_TreeException_if_female_tries_to_get_married()
    {
        $this->expectException(TreeException::class);

        $tree   = $this->createTreeable();
        $root   = $tree->createRoot($this->makeNodeable()->toArray());
        // create new node in database table
        $man   = $root->newSon($this->makeNodeable()->toArray());
        $woman = $root->newDaughter($this->makeNodeable()->toArray());

        // wrong date is provided
        $woman->getMarriedWith($man);
    }

    /** @test 5 */
    public function it_can_not_get_married_with_one_who_already_married_with()
    {
        $this->expectException(TreeException::class);

        $tree   = $this->createTreeable();
        $root   = $tree->createRoot($this->makeNodeable()->toArray());
        // create new node in database table
        $man   = $root->newSon($this->makeNodeable()->toArray());
        $woman = $root->newDaughter($this->makeNodeable()->toArray());

        //Getting married with woman
        $man->getMarriedWith($woman);
        //trying to get married with same woman
        $man->getMarriedWith($woman);
    }

    /** @test 6 */
    public function it_throws_TreeException_when_trying_getting_married_with_himself()
    {
        $this->expectException(TreeException::class);
        // create new node in database table
        $tree   = $this->createTreeable();
        $root   = $tree->createRoot($this->makeNodeable()->toArray());
        // create new node in database table
        $man   = $root->newSon($this->makeNodeable()->toArray());

        //Getting married with woman
        $man->getMarriedWith($man);
    }

    // /** @test 7 */
    // public function it_can_save_marriage_info_in_database_if_provided()
    // {
    //     // create new node in database table
    //     $man   = $this->createMaleNode();
    //     $woman = $this->createFemaleNode(['location'=>'aa.aa']);

    //     $data = ['date_of_marriage'=>'2000/01/01','marriage_desc'=>'Marriage Description'];
    //     //Getting married with woman
    //     $man->getMarriedWith($woman, $data);

    //     $this->assertDatabaseHas('marriages', ['husband_id'=>$man->id, 'wife_id'=>$woman->id, 'date_of_marriage'=>$data['date_of_marriage'], 'marriage_desc'=>$data['marriage_desc']]);
    // }
}