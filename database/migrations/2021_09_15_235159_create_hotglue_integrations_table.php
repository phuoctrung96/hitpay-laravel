<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHotglueIntegrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hotglue_integrations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('business_id');
            $table->string('source');
            $table->string('flow');
            $table->boolean('connected')->default(1);
            $table->timestamp('initial_sync_date')->nullable();
            $table->timestamp('last_sync_date')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hotglue_integrations');
    }
}
