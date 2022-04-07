<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessPartnersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_partners', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('user_id')->unique();
            $table->string('business_id')->nullable();
            $table->string('referral_code')->unique();
            $table->string('website');
            $table->unsignedDecimal('commission_percent')->nullable();
            $table->unsignedDecimal('commission_fixed')->nullable();
            $table->enum('status', \App\Enumerations\BusinessPartnerStatus::listConstants());
            $table->text('services');
            $table->text('platforms');
            $table->text('short_description');
            $table->text('special_offer');
            $table->string('logo_path');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_partners');
    }
}
