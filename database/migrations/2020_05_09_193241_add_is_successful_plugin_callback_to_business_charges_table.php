<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsSuccessfulPluginCallbackToBusinessChargesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_charges', function (Blueprint $table) {
            $table->boolean('is_successful_plugin_callback')->default(true);
            $table->json('plugin_data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_charges', function (Blueprint $table) {
            $table->dropColumn('is_successful_plugin_callback');
            $table->dropColumn('plugin_data');
        });
    }
}
