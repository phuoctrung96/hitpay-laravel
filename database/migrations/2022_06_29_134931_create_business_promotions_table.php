<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessPromotionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_promotions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('promotion_type', 100);
            $table->uuid('promotion_id');
            $table->tinyInteger('applies_to_type');
            $table->uuid('applies_to_id');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_promotions');
    }
}
