<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPeriodicSyncToHotglueIntegrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hotglue_integrations', function (Blueprint $table) {
            $table->boolean('periodic_sync')->default(0)->after('connected');
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
            $table->dropColumn('periodic_sync');
        });
    }
}
