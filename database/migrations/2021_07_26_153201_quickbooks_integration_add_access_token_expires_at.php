<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class QuickbooksIntegrationAddAccessTokenExpiresAt extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quickbook_integrations', function (Blueprint $table) {
            $table->timestamp('access_token_expires_at')->nullable();
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
            $table->dropColumn('access_token_expires_at');
        });
    }
}
