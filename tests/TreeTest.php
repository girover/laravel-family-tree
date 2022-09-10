<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Exceptions\TreeException;
use Girover\Tree\Tests\Traits\Factoryable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class TreeTest extends TestCase
{
    use DatabaseTransactions, Factoryable;

    /**
     * -------------------------------------------
     * testing CreateRoot method
     * --------------------------------------------
     */
    /** @test */
    public function it_can_create_a_tree()
    {
        // dd(DB::select('SHOW TABLES'));
        // Create new Tree in database.
        $tree = $this->createTreeable();
       
        $this->assertDatabaseHas('treeables', [
            'name'=>$tree->name,
        ]);
    }
    public function it_can_create_root_for_a_tree()
    {
        // Create new Tree in database.
        $tree = $this->createTreeable();
        // Create Root node for the created tree.
        $root = $tree->createRoot(['name'=>'majed', 'b_date'=>'1983-04-10']);
       
        $this->assertDatabaseHas('treeables', [
            'name'=>$tree->name,
        ]);
        $this->assertDatabaseHas('nodeables', [
            'name'=>$root->name,
        ]);

        $this->assertTrue($root->isRoot());
    }

    /** @test */
    public function test_can_not_create_root_for_tree_if_root_exists()
    {
        $this->expectException(TreeException::class);        
        // Create new Tree in database.
        $tree = $this->createTreeable();
        // Create Root node for the created tree.
        $root = $tree->createRoot($this->makeMaleNodeable()->toArray());

        $this->assertDatabaseHas('tree_nodes', [
            'treeable_id'=>$tree->id,
            'nodeable_id'=>$root->getKey(),
        ]);
        $tree->createRoot($this->makeNodeable()->toArray());
    }

    /** @test */
    public function test_can_not_create_root_for_tree_if_no_data_are_provided()
    {
        $this->expectException(TreeException::class);        
        // Create new Tree in database.
        $tree = $this->createTreeable();
        // try to create Root node for the created tree, when no data are provided.
        $tree->createRoot([]); 
    }

    public function test_create_root_method_can_accept_nodeable_object()
    {
        // Create new Tree in database.
        $tree = $this->createTreeable();
        // create nodeable model
        $nodeable = $this->createNodeable();

        $root = $tree->createRoot($nodeable);

        $this->assertDatabaseHas('treeables', [
            'name'=>$tree->name,
        ]);

        $this->assertDatabaseHas('nodeables', [
            'id'=>$nodeable->id
        ]);

        $this->assertTrue($root->isRoot());
    }

    public function test_newRoot_can_create_new_root()
    {
        // Create new Tree in database.
        $tree = $this->createTreeable();
        
        $nodeable     = $this->createNodeable();
        $new_nodeable = $this->createNodeable();

        $root     = $tree->createRoot($nodeable);
        $this->assertTrue($root->isRoot());
        
        $new_root = $tree->newRoot($new_nodeable);
        $root->refresh();
        $this->assertFalse($root->isRoot());
        $this->assertTrue($new_root->isRoot());
        $this->assertTrue(($new_root)->isFatherOf($root));

    }

    /** @test */
    public function test_can_not_create_new_root_for_tree_if_no_data_are_provided()
    {
        // Create new Tree in database.
        $tree = $this->createTreeable();
        $this->assertDatabaseHas('treeables', ['id'=>$tree->getKey()]);
        // Create Root node for the created tree.
        $root = $tree->createRoot($this->makeMaleNodeable()->toArray());
        $this->assertDatabaseHas('tree_nodes', ['treeable_id'=>$tree->getKey()]);
        // Trying to create root with no data provided
        $new_root = $tree->newRoot([]);
        $this->assertNull($new_root);        
    }

    public function test_draw_method_returns_html_string()
    {
        $tree = $this->createTreeable();
        
        $nodeable = $this->createMaleNodeable();

        $root = $tree->createRoot($nodeable);

        $son1 = $root->newSon($this->makeNodeable()->toArray());
        $son2 = $root->newSon($this->makeNodeable()->toArray());

        $html = $tree->render();

        $this->assertStringContainsString($root->name, $html);
        $this->assertStringContainsString($son1->name, $html);
        $this->assertStringContainsString($son2->name, $html);
        $this->assertStringContainsString('<div id="tree" class="tree">', $html);
    }
}
