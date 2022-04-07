<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessPaymentProviderRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_payment_provider_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_payment_provider_id')->index('index_business_payment_provider_id');
            $table->string('channel', 16)->nullable();
            $table->string('method', 32);
            $table->unsignedInteger('fixed_amount')->nullable();
            $table->decimal('percentage', 5, 4)->nullable();
            $table->timestamps();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->index($columns = [
                'business_payment_provider_id',
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
        Schema::dropIfExists('business_payment_provider_rates');
    }
}
