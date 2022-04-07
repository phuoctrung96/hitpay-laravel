<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BusinessGatewayProvidersAddXeroBrandingTheme extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_gateway_providers', function (Blueprint $table) {
            $table->string('xero_branding_theme')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_gateway_providers', function (Blueprint $table) {
            $table->dropColumn('xero_branding_theme');
        });
    }
}
