<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessVerificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_verifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id')->index();
            $table->string('type');
            $table->string('identification')->nullable();
            $table->string('name')->nullable();
            $table->json('submitted_data')->nullable();
            $table->json('my_info_data')->nullable();
            $table->timestamps();
            $table->timestamp('verified_at')->nullable();
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->boolean('verified_wit_my_info_sg')->default(false);
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
            $table->dropColumn('verified_wit_my_info_sg');
        });

        Schema::dropIfExists('business_verifications');
    }
}
