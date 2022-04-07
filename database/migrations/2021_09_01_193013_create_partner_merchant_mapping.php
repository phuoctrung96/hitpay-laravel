<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePartnerMerchantMapping extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('partner_merchant_mapping', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('business_id');
            $table->unsignedBigInteger('business_partner_id');
            $table->timestamps();

            $table->unique(['business_id', 'business_partner_id']);
            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
                ->onDelete('cascade');

            $table->foreign('business_partner_id')
                ->references('id')
                ->on('business_partners')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('partner_merchant_mapping');
    }
}
