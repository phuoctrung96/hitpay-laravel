<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class QuickbooksIntegrationAddSyncDate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quickbook_integrations', function (Blueprint $table) {
            $table->date('initial_sync_date')->nullable();
            $table->date('last_sync_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quickbook_integrations', function (Blueprint $table) {
            $table->dropColumn('initial_sync_date');
            $table->dropColumn('last_sync_date');
        });
    }
}
