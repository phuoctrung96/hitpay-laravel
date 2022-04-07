<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterInvoice extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_invoices', function (Blueprint $table) {
            $table->string('currency', 3)->after('status');
            $table->timestamp('invoice_date')->after('amount');
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
            $table->dropColumn('currency');
            $table->dropColumn('invoice_date');
        });
    }
}
