<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIndexShopDomainToBusinessShopifyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_shopify_stores', function (Blueprint $table) {
            $table->index('shopify_domain');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_shopify_stores', function (Blueprint $table) {
            $table->dropIndex('shopify_domain');
        });
    }
}
