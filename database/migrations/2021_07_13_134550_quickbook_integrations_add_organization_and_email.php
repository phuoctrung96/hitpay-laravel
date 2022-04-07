<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class QuickbookIntegrationsAddOrganizationAndEmail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quickbook_integrations', function (Blueprint $table) {
            $table->string('organization')->nullable();
            $table->string('email')->nullable();
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
            $table->dropColumn('organization');
            $table->dropColumn('email');
        });
    }
}
