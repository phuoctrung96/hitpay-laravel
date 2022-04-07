<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessOrderedProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_ordered_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_order_id')->index('index_business_order_id');
            $table->uuid('business_product_id');
            $table->string('stock_keeping_unit', 32)->nullable();
            $table->string('name', 128)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('weight')->nullable();
            $table->unsignedMediumInteger('length')->nullable();
            $table->unsignedMediumInteger('width')->nullable();
            $table->unsignedMediumInteger('depth')->nullable();
            $table->string('variation_key_1')->nullable();
            $table->string('variation_value_1')->nullable();
            $table->string('variation_key_2')->nullable();
            $table->string('variation_value_2')->nullable();
            $table->string('variation_key_3')->nullable();
            $table->string('variation_value_3')->nullable();
            $table->unsignedInteger('quantity');
            $table->string('tax_name')->nullable();
            $table->decimal('tax_rate', 5, 4)->default(0);
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('price');
            $table->text('remark')->nullable();
            $table->uuid('business_image_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index($columns = [
                'business_order_id',
                'id',
            ], _blueprint_hash_columns('index', $columns));

            $table->index($columns = [
                'business_order_id',
                'business_product_id',
            ], _blueprint_hash_columns('index', $columns));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_ordered_products');
    }
}
