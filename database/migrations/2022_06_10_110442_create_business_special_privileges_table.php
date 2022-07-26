<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessSpecialPrivilegesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_special_privileges', function (Blueprint $table) {
            $table->uuid('business_id');
            $table->string('special_privilege');
            $table->timestamp('granted_at');

            $table->unique([
                'business_id',
                'special_privilege',
            ], 'business_special_privileges_unique_1');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_special_privileges');
    }
}
