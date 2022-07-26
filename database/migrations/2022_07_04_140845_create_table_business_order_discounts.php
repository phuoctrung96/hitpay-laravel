<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableBusinessOrderDiscounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_ordered_discounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('order_id');
            $table->uuid('discount_id');
            $table->json('discount_data')->nullable();
            $table->timestamps();

            $table->index($columns = [
                'business_id',
                'id',
            ], _blueprint_hash_columns('index', $columns));

            $table->index($columns = [
                'business_id',
                'discount_id',
            ], _blueprint_hash_columns('index_business_discount', $columns));

            $table->index($columns = [
                'order_id',
                'discount_id',
            ], _blueprint_hash_columns('index_order_discount', $columns));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_ordered_discounts');
    }
}
