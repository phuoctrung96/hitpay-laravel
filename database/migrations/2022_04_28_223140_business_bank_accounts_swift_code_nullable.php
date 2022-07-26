<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BusinessBankAccountsSwiftCodeNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_bank_accounts', function($table)
        {
            $table->string('bank_swift_code', 64)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_bank_accounts', function($table)
        {
            $table->string('bank_swift_code', 64)->change();
        });
    }
}
