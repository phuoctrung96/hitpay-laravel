<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessBankAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_bank_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id')->index();
            $table->char('country', 2);
            $table->char('currency', 3);
            $table->string('bank_swift_code', 64);
            $table->string('bank_routing_number', 64)->nullable();
            $table->string('number', 64);
            $table->string('holder_name')->nullable();
            $table->string('holder_type', 32)->nullable();
            $table->string('remark')->nullable();
            $table->string('stripe_platform', 32)->nullable();
            $table->string('stripe_external_account_id', 64)->nullable();
            $table->boolean('stripe_external_account_default');
            $table->json('data')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_bank_accounts');
    }
}
