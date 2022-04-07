<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameChangeLengthProductCategoriesProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_products', function(Blueprint $table) {
            $table->string('business_product_category_id', 255)->change();;
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_products', function(Blueprint $table) {
        $table->uuid('business_product_category_id')->nullable()->index('index_business_product_category_id')->change();
        });
    }
}
