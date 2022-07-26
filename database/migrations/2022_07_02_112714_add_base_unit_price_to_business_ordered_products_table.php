<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBaseUnitPriceToBusinessOrderedProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_ordered_products', function (Blueprint $table) {
            $table->unsignedBigInteger('base_unit_price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_ordered_products', function (Blueprint $table) {
            $table->dropColumn('base_unit_price');
        });
    }
}
