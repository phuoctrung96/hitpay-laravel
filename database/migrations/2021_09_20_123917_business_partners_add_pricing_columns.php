<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BusinessPartnersAddPricingColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_partners', function (Blueprint $table) {
            $table->string('stripe_channel')->nullable();
            $table->string('stripe_method')->nullable();
            $table->float('stripe_percentage')->nullable();
            $table->float('stripe_fixed_amount');
            $table->string('paynow_channel')->nullable();
            $table->string('paynow_method')->nullable();
            $table->float('paynow_percentage')->nullable();
            $table->float('paynow_fixed_amount');
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
            $table->dropColumn('stripe_channel');
            $table->dropColumn('stripe_method');
            $table->dropColumn('stripe_percentage');
            $table->dropColumn('stripe_fixed_amount');
            $table->dropColumn('paynow_channel');
            $table->dropColumn('paynow_method');
            $table->dropColumn('paynow_percentage');
            $table->dropColumn('paynow_fixed_amount');
        });
    }
}
