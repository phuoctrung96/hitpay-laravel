<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInvoiceNumberToBusinessInvoiceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_invoices', function (Blueprint $table) {
            $table->string('invoice_number')->after('reference');
            $table->string('memo')->after('products')->nullable();
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
            $table->dropColumn('invoice_number');
            $table->dropColumn('memo');
        });
    }
}
