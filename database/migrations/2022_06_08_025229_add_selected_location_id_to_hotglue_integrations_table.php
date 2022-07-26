<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSelectedLocationIdToHotglueIntegrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hotglue_integrations', function (Blueprint $table) {
            $table->string('selected_location_id', 255)->after('connected')->nullable();
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
            $table->dropColumn('selected_location_id');
        });
    }
}
