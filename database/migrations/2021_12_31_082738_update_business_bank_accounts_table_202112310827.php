<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBusinessBankAccountsTable202112310827 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_bank_accounts', function (Blueprint $table) {
            $table->boolean('hitpay_default')->default(false)->after('remark');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_bank_accounts', function (Blueprint $table) {
            $table->dropColumn('hitpay_default');
        });
    }
}
