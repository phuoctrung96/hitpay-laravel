<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddItemIdToHotglueProductTrackersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hotglue_product_trackers', function (Blueprint $table) {
            $table->string('item_id', 255)->after('hotglue_job_id')->nullable()->index();
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
            $table->dropColumn('item_id');
        });
    }
}
