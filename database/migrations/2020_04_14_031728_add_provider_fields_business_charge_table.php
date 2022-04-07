<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProviderFieldsBusinessChargeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_charges', function (Blueprint $table) {
            $table->string('plugin_provider')->nullable();
            $table->string('plugin_provider_reference')->nullable();
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
            $table->dropColumn('plugin_provider');
            $table->dropColumn('plugin_provider_reference');
        });
    }
}
