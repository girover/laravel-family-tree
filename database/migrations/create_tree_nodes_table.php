<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tree_nodes', function (Blueprint $table) {
            $table->id('node_id'); // unsigned big integer
            $table->bigInteger('nodeable_id')->unsigned();
            $table->bigInteger('treeable_id')->unsigned();
            $table->bigInteger('node_parent_id')->unsigned()->nullable();
            $table->enum('node_gender', ['m', 'f'])->default('m');
            $table->string('node_photo', 255)->nullable();         // Path to the avatar
            // $table->timestamps();
            
            $table->foreignId('node_parent_id')->references('node_id')
                  ->on('tree_nodes')->onDelete('cascade');
            $table->index('treeable_id');
            $table->unique('nodeable_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tree_nodes');
    }
}
