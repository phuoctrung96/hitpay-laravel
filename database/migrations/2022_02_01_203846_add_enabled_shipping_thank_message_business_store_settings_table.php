<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEnabledShippingThankMessageBusinessStoreSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_store_settings', function (Blueprint $table) {
            $table->boolean('enabled_shipping')->default(1);
            $table->string('thank_message', 1000)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_store_settings', function (Blueprint $table) {
            $table->dropColumn('enabled_shipping');
            $table->dropColumn('thank_message');
        });
    }
}
