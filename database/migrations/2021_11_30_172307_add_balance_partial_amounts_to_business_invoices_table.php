<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBalancePartialAmountsToBusinessInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_invoices', function (Blueprint $table) {
            $table->boolean('allow_partial_payments')->default(0);
            $table->double('balance_amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_invoices', function (Blueprint $table) {
            $table->dropColumn('allow_partial_payments');
            $table->dropColumn('balance_amount');
        });
    }
}
