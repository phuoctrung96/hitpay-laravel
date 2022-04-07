<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BusinessPartnersMakePriceFieldsNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_partners', function (Blueprint $table) {
            DB::statement('ALTER TABLE `business_partners` CHANGE `stripe_fixed_amount` `stripe_fixed_amount` DOUBLE(8,2) NULL');
            DB::statement('ALTER TABLE `business_partners` CHANGE `paynow_fixed_amount` `paynow_fixed_amount` DOUBLE(8,2) NULL');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_partners', function (Blueprint $table) {
            //
        });
    }
}
