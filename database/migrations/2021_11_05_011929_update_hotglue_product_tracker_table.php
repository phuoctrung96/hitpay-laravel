<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateHotglueProductTrackerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hotglue_product_trackers', function(Blueprint $table) {
            $table->index('stock_keeping_unit');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hotglue_product_trackers', function(Blueprint $table) {
            $table->dropIndex(['stock_keeping_unit']);
        });
    }
}
