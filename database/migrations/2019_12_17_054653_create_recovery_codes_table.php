<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecoveryCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recovery_codes', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->string('code', 32);
            $table->timestamp('used_at')->nullable();
            $table->softDeletes();

            $table->unique($columns = [
                'user_id',
                'code',
            ], _blueprint_hash_columns('unique', $columns));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('recovery_codes');
    }
}
