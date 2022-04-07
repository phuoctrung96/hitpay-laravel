<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBusinessesTableForXeroFee extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->char('xero_fee_account_type')->nullable()->after('shopify_id');
            $table->char('xero_fee_account_id')->nullable()->after('shopify_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('xero_fee_account_type');
            $table->dropColumn('xero_fee_account_id');
        });
    }
}
