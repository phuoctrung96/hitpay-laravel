<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateColumnVerificationProvider extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_verifications', function (Blueprint $table) {
            $table->string('verification_provider')->default('myinfo')->index();
            $table->string('verification_provider_status')->nullable()->index();
            $table->string('verification_provider_account_id')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_verifications', function (Blueprint $table) {
            $table->dropColumn('verification_provider');
            $table->dropColumn('verification_provider_status');
            $table->dropColumn('verification_provider_account_id');
        });
    }
}
