<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsCampaignCashbackToBusinessRefundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_refunds', function (Blueprint $table) {
            $table->boolean('is_campaign_cashback')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_refunds', function (Blueprint $table) {
            $table->dropColumn('is_campaign_cashback');
        });
    }
}
