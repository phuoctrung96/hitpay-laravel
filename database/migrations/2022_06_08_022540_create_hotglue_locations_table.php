<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHotglueLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hotglue_locations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ecommerce_location_id', 255);
            $table->unsignedBigInteger('hotglue_integration_id');
            $table->string('name');
            $table->boolean('active')->default(1);
            $table->timestamps();

            $table->foreign('hotglue_integration_id')->references('id')->on('hotglue_integrations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hotglue_locations');
    }
}
