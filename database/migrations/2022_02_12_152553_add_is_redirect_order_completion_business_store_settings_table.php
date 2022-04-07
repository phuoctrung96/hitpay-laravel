<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsRedirectOrderCompletionBusinessStoreSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_store_settings', function (Blueprint $table) {
            $table->boolean('is_redirect_order_completion')->default(0)->after('can_pick_up');
            $table->string('url_redirect_order_completion', 500)->nullable()->after('is_redirect_order_completion');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_store_settings', function (Blueprint $table) {
            $table->dropColumn('is_redirect_order_completion');
            $table->dropColumn('url_redirect_order_completion');
        });
    }
}
