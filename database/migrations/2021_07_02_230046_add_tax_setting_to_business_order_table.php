<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTaxSettingToBusinessOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_orders', function (Blueprint $table) {
            $table->uuid('tax_setting_id')->nullable()->after('coupon_amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_orders', function (Blueprint $table) {
            $table->dropColumn('tax_setting_id');
        });
    }
}
