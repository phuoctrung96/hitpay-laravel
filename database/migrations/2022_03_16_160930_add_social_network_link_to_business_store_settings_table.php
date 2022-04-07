<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSocialNetworkLinkToBusinessStoreSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_store_settings', function (Blueprint $table) {
            $table->string('url_facebook', 255)->nullable()->after('url_redirect_order_completion');
            $table->string('url_instagram', 255)->nullable()->after('url_facebook');
            $table->string('url_twitter', 255)->nullable()->after('url_instagram');
            $table->string('url_tiktok', 255)->nullable()->after('url_twitter');
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
            $table->dropColumn('url_facebook');
            $table->dropColumn('url_instagram');
            $table->dropColumn('url_twitter');
            $table->dropColumn('url_tiktok');
        });
    }
}
