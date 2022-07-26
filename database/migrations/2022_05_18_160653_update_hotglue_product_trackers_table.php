<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateHotglueProductTrackersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hotglue_product_trackers', function (Blueprint $table) {
            $table->string('stock_keeping_unit', 255)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hotglue_product_trackers', function (Blueprint $table) {
            $table->string('stock_keeping_unit', 32)->change();
        });
    }
}
