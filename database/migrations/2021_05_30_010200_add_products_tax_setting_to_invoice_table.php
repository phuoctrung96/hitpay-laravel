<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductsTaxSettingToInvoiceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_invoices', function (Blueprint $table) {
            $table->double('amount_no_tax')->after('amount');
            $table->string('products', 1000)->nullable()->after('amount_no_tax');
            $table->uuid('tax_settings_id')->nullable()->after('products');
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
            $table->dropColumn('products');
            $table->dropColumn('tax_settings_id');
            $table->dropColumn('amount_no_tax');
        });
    }
}
