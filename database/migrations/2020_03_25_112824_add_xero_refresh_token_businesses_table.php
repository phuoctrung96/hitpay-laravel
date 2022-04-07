<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddXeroRefreshTokenBusinessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->longText('xero_refresh_token')->nullable()->after('shopify_id');
            $table->longText('xero_tenant_id')->nullable()->after('shopify_id');
            $table->longText('xero_contact_id')->nullable()->after('shopify_id');
            $table->longText('xero_account_id')->nullable()->after('shopify_id');
            $table->longText('xero_refund_account_id')->nullable()->after('shopify_id');
            $table->char('xero_sales_account_type')->nullable()->after('shopify_id');
            $table->char('xero_refund_account_type')->nullable()->after('shopify_id');
            $table->date('xero_sync_date')->nullable()->after('shopify_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('xero_refresh_token');
            $table->dropColumn('xero_tenant_id');
            $table->dropColumn('xero_contact_id');
            $table->dropColumn('xero_account_id');
            $table->dropColumn('xero_sales_account_type');
            $table->dropColumn('xero_refund_account_type');
            $table->dropColumn('xero_sync_date');
            $table->dropColumn('xero_refund_account_id');
        });
    }
}
