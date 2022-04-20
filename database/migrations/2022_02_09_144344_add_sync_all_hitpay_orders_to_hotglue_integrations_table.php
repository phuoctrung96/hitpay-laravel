<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSyncAllHitpayOrdersToHotglueIntegrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hotglue_integrations', function (Blueprint $table) {
            $table->boolean('sync_all_hitpay_orders')->default(0)->after('periodic_sync');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hotglue_integrations', function (Blueprint $table) {
            $table->dropColumn('sync_all_hitpay_orders');
        });
    }
}
