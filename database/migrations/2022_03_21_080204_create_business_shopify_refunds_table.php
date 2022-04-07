<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessShopifyRefundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_shopify_refunds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('refund_id', 200);
            $table->string('payment_id', 200);
            $table->string('gid', 200);
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index($columns = [
                'business_id',
                'refund_id',
                'payment_id',
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
        Schema::dropIfExists('business_shopify_refunds');
    }
}
