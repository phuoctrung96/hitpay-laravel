<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBusinessPaymentProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_payment_providers', function (Blueprint $table) {
          $table->string('onboarding_status');          
        });        

        DB::table('business_payment_providers')
          ->whereIn('payment_provider', ['dbs_sg', 'stripe_sg', 'shopee_pay'])
          ->update([
              'onboarding_status' => 'success'
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
          $table->dropColumn('onboarding_status');
        });
    }
}
