<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTablesForPlatformApis extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->boolean('platform_enabled')->default(false);
            $table->string('platform_key', 64)->nullable()->index('index_platform_key');
            $table->decimal('commission_rate', 5, 4)->default(0);
        });

        Schema::table('business_payment_requests', function (Blueprint $table) {
            $table->string('platform_business_id')->nullable()->index('index_platform_business_id');
            $table->decimal('commission_rate', 5, 4)->default(0);
        });

        Schema::table('business_charges', function (Blueprint $table) {
            $table->string('platform_business_id')->nullable()->index('index_platform_business_id');
            $table->decimal('commission_rate', 5, 4)->default(0);
            $table->unsignedBigInteger('commission_amount')->default(0);
            $table->unsignedBigInteger('home_currency_commission_amount')->default(0);
        });

        Schema::create('business_commissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id')->index('index_business_id');
            $table->string('payment_provider', 32);
            $table->string('payment_provider_account_id', 64)->nullable();
            $table->string('payment_provider_transfer_id', 64)->nullable();
            $table->string('payment_provider_transfer_type', 32)->nullable();
            $table->string('payment_provider_transfer_method', 32);
            $table->char('currency', 3);
            $table->unsignedBigInteger('amount');
            $table->string('remark')->nullable();
            $table->string('status', 32);
            $table->json('data')->nullable();
            $table->uuid('executor_id')->nullable();
            $table->ipAddress('request_ip_address');
            $table->string('request_user_agent', 1024)->nullable();
            $table->string('request_method', 8)->nullable();
            $table->string('request_url', 1024)->nullable();
            $table->char('request_country', 2)->nullable();
            $table->json('request_data')->nullable();
            $table->timestamps();
        });

        Schema::create('business_charge_commission', function (Blueprint $table) {
            $table->uuid('commission_id')->index('index_commission_id');
            $table->uuid('charge_id')->index('index_charge_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_charge_commission');
        Schema::dropIfExists('business_commissions');

        Schema::table('business_charges', function (Blueprint $table) {
            $table->dropColumn('home_currency_commission_amount');
            $table->dropColumn('commission_amount');
            $table->dropColumn('commission_rate');
            $table->dropColumn('platform_business_id');
        });

        Schema::table('business_payment_requests', function (Blueprint $table) {
            $table->dropColumn('commission_rate');
            $table->dropColumn('platform_business_id');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('commission_rate');
            $table->dropColumn('platform_key');
            $table->dropColumn('platform_enabled');
        });
    }
}
