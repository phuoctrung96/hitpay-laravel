<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessPerson extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_persons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id')->index();
            $table->string('first_name', 255)->nullable();
            $table->string('last_name', 255)->nullable();
            $table->string('alias_name', 255)->nullable();
            $table->date('dob')->nullable();
            $table->text('address')->nullable();
            $table->text('address2')->nullable();
            $table->string('country', 255)->nullable();
            $table->string('city', 255)->nullable();
            $table->string('state', 255)->nullable();
            $table->string('phone', 255)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('id_number', 255)->nullable();
            $table->string('title', 255)->nullable();
            $table->json('relationship')->nullable();
            $table->string('stripe_person_id', 255)->nullable();
            $table->json('data')->nullable();
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
        Schema::dropIfExists('business_person');
    }
}
