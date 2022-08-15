<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarriagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('marriages', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('nodeable_husband_id')->unsigned()->nullable();
            $table->bigInteger('nodeable_wife_id')->unsigned()->nullable();
            $table->boolean('divorced')->default(false);
            $table->timestamps();

            $table->unique(['nodeable_husband_id', 'nodeable_wife_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('marriages');
    }
}
