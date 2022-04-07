<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStripeTerminalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stripe_terminals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_stripe_terminal_location_id')->nullable();
            $table->string('name');
            $table->string('stripe_terminal_id', 64);
            $table->string('device_type', 128);
            $table->string('remark')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index($columns = [
                'business_stripe_terminal_location_id',
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
        Schema::dropIfExists('stripe_terminals');
    }
}
