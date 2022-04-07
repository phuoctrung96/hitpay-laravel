<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPromoBannerToBusinessCouponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_coupons', function (Blueprint $table) {
            $table->boolean('is_promo_banner')->nullable()->after('percentage');
            $table->string('banner_text', 1000)->nullable()->after('is_promo_banner');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_coupons', function (Blueprint $table) {
            $table->dropColumn('is_promo_banner');
            $table->dropColumn('banner_text');
        });
    }
}
