<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BusinessChargesAddQuickbooksImported extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_charges', function (Blueprint $table) {
            $table->boolean('quickbooks_imported')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_charges', function (Blueprint $table) {
            $table->dropColumn('quickbooks_imported');
        });
    }
}
