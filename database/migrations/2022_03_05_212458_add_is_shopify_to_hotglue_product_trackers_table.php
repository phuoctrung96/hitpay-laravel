<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsShopifyToHotglueProductTrackersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hotglue_product_trackers', function (Blueprint $table) {
            $table->boolean('is_shopify')->default(0)->after('manage_inventory');
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
            $table->dropColumn('is_shopify');
        });
    }
}
