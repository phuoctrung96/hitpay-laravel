<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('identifier', 32)->nullable()->unique('unique_identifier');
            $table->uuid('user_id');
            $table->string('payment_provider', 32);
            $table->string('payment_provider_customer_id', 64)->nullable();
            $table->string('name');
            $table->string('display_name', 64)->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number', 32)->nullable();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code', 16)->nullable();
            $table->char('country', 2)->index('index_country');
            $table->string('category', 32)->nullable();
            $table->text('introduction')->nullable();
            $table->string('statement_description')->nullable();
            $table->date('founding_date')->nullable();
            $table->string('locale', 8);
            $table->char('currency', 3);
            $table->boolean('can_pick_up');
            $table->string('shopify_id', 64)->nullable();
            $table->string('shopify_name')->nullable();
            $table->string('shopify_domain')->nullable();
            $table->string('shopify_token')->nullable();
            $table->char('shopify_currency', 3)->nullable();
            $table->string('shopify_location_id', 64)->nullable();
            $table->json('shopify_data')->nullable();
            $table->boolean('migrated')->default(false);
            $table->timestamps();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_number_verified_at')->nullable();
            $table->timestamp('banned_at')->nullable();
            $table->softDeletes();

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
        Schema::dropIfExists('businesses');
    }
}
