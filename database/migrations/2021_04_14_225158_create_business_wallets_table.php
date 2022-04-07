<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessWalletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_wallets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('name', 32)->nullable();
            $table->char('type', 16);
            $table->char('currency', 3);
            $table->bigInteger('balance')->default(0);
            $table->bigInteger('reserve_balance')->default(0);
            $table->string('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->timestamp('last_cleared_at')->nullable();

            $table->unique([
                'business_id',
                'type',
                'currency',
            ], 'composite_primary_key');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_wallets');
    }
}
