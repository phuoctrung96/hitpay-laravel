<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('user_id')->nullable();
            $table->string('business_customer_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone_number', 32)->nullable();
            $table->string('customer_street')->nullable();
            $table->string('customer_city')->nullable();
            $table->string('customer_state')->nullable();
            $table->string('customer_postal_code', 16)->nullable();
            $table->char('customer_country', 2)->nullable();
            $table->boolean('customer_pickup');
            $table->string('channel', 16);
            $table->char('currency', 3);
            $table->string('automatic_discount_name')->nullable();
            $table->unsignedBigInteger('automatic_discount_amount')->default(0);
            $table->unsignedBigInteger('line_item_price')->default(0);
            $table->unsignedBigInteger('line_item_discount_amount')->default(0);
            $table->unsignedBigInteger('line_item_tax_amount')->default(0);
            $table->string('automatic_discount_reason')->nullable();
            $table->unsignedBigInteger('additional_discount_amount')->default(0);
            $table->uuid('business_shipping_id')->nullable();
            $table->string('shipping_method')->nullable();
            $table->unsignedBigInteger('shipping_amount')->default(0);
            $table->string('shipping_tax_name')->nullable();
            $table->decimal('shipping_tax_rate', 5, 4)->default(0);
            $table->unsignedBigInteger('shipping_tax_amount')->default(0);
            $table->unsignedBigInteger('amount')->default(0);
            $table->string('reference')->nullable();
            $table->json('messages')->nullable();
            $table->text('remark')->nullable();
            $table->string('status', 32);
            $table->uuid('executor_id')->nullable();
            $table->ipAddress('request_ip_address');
            $table->string('request_user_agent', 1024)->nullable();
            $table->string('request_method', 8)->nullable();
            $table->string('request_url', 1024)->nullable();
            $table->char('request_country', 2)->nullable();
            $table->json('request_data')->nullable();
            $table->timestamps();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->index($columns = [
                'business_id',
                'id',
            ], _blueprint_hash_columns('index', $columns));

            $table->index($columns = [
                'business_id',
                'business_customer_id',
            ], _blueprint_hash_columns('index', $columns));

            $table->index($columns = [
                'user_id',
                'id',
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
        Schema::dropIfExists('business_orders');
    }
}
