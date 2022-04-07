<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateBusinessPaymentProvidersTable202111111617 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_payment_providers', function (Blueprint $table) {
            $table->string('payment_provider_account_type', 32)->nullable()->after('payment_provider');
            $table->boolean('payment_provider_account_ready')->nullable()->after('payment_provider_account_id');
        });

        DB::table('business_payment_providers')->where('payment_provider', 'like', 'stripe_%')->update([
            'payment_provider_account_type' => 'standard',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_payment_providers', function (Blueprint $table) {
            $table->dropColumn('payment_provider_account_ready');
            $table->dropColumn('payment_provider_account_type');
        });
    }
}
