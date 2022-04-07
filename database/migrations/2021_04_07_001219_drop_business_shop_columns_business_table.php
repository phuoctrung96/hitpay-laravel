<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropBusinessShopColumnsBusinessTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('businesses', function ($table) {
            $table->dropColumn('seller_notes');
            $table->dropColumn('shop_state');
            $table->dropColumn('enable_datetime');
            $table->dropColumn('slots');
            $table->dropColumn('can_pick_up');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('businesses', function ($table) {
            $table->string('seller_notes', 1000)->nullable();
            $table->boolean('shop_state')->default(1);
            $table->timestamp('enable_datetime')->nullable();
            $table->string('slots', 1000)->nullable();
            $table->boolean('can_pick_up')->nullable();
        });
    }
}
