<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBusinessWalletTopUpIntents202112141707 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_wallet_top_up_intents', function (Blueprint $table) {
            $table->string('additional_reference')->nullable()->after('executor_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_wallet_top_up_intents', function (Blueprint $table) {
            $table->dropColumn('additional_reference');
        });
    }
}
