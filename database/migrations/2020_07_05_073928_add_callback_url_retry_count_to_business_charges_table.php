<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCallbackUrlRetryCountToBusinessChargesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_charges', function (Blueprint $table) {
            $table->unsignedSmallInteger('callback_url_retry_count')->default(0);
            $table->string('callback_url_status')->default('pending');
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
            $table->dropColumn('callback_url_retry_count');
            $table->dropColumn('callback_url_status');
        });
    }
}
