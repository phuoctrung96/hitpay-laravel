<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('parent_id')->nullable()->index('index_parent_id');
            $table->uuid('business_product_category_id')->nullable()->index('index_business_product_category_id');
            $table->string('stock_keeping_unit', 32)->nullable();
            $table->string('name', 128)->nullable();
            $table->string('headline')->nullable();
            $table->mediumText('description')->nullable();
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
            $table->char('currency', 3)->nullable();
            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedMediumInteger('quantity')->nullable();
            $table->unsignedMediumInteger('quantity_alert_level')->nullable();
            $table->string('shopify_id', 64)->nullable();
            $table->string('shopify_inventory_item_id', 64)->nullable();
            $table->string('shopify_stock_keeping_unit', 64)->nullable();
            $table->json('shopify_data')->nullable();
            $table->uuid('business_tax_id')->nullable();
            $table->timestamps();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->index($columns = [
                'business_id',
                'id',
            ], _blueprint_hash_columns('index', $columns));

            $table->unique($columns = [
                'business_id',
                'stock_keeping_unit',
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
        Schema::dropIfExists('business_products');
    }
}
