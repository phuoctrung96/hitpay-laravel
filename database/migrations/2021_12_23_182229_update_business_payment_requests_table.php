<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBusinessPaymentRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::table('business_payment_requests', function (Blueprint $table) {
        $table->boolean('add_admin_fee')->default(false);
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::table('business_payment_requests', function (Blueprint $table) {
        $table->dropColumn('add_admin_fee');
      });
    }
}