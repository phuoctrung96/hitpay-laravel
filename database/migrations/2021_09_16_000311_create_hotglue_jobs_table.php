<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHotglueJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hotglue_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('hotglue_integration_id');
            $table->string('job_id');
            $table->string('job_name');
            $table->string('status');
            $table->string('aws_path');
            $table->timestamp('sync_date')->nullable();
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
        Schema::dropIfExists('hotglue_jobs');
    }
}
