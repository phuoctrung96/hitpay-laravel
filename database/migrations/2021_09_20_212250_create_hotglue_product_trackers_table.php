<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHotglueProductTrackersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hotglue_product_trackers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('hotglue_job_id');
            $table->string('stock_keeping_unit', 32)->nullable();
            $table->string('name', 128)->nullable();
            $table->mediumText('description')->nullable();
            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedMediumInteger('quantity')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('published')->default(1);
            $table->boolean('manage_inventory')->default(1);
            $table->timestamps();

            $table->foreign('hotglue_job_id')->references('id')->on('hotglue_jobs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hotglue_product_trackers');
    }
}
