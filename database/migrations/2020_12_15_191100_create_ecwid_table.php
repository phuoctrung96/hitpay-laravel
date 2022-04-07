<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEcwidTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ecwid', function (Blueprint $table) {
            $table->bigIncrements('ID');
            $table->string('order_id', 20)->nullable();
			$table->string('store_id', 20)->nullable();
			$table->string('token', 200)->nullable();			
        });
    }	

    /**
     * Reverse the migrations.
     *
     * @return void
     */   
    public function down()
    {
        Schema::dropIfExists('ecwid');
    }
}
