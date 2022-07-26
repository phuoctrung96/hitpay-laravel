<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCouponTypeToBusinessCouponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_coupons', function (Blueprint $table) {
            $table->tinyInteger('coupon_type')->default('1')->index();
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
            $table->dropColumn('coupon_type');
        });
    }
}
