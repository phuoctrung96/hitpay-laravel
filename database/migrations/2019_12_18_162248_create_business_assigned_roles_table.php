<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessAssignedRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_assigned_roles', function (Blueprint $table) {
            $table->uuid('business_role_id');
            $table->uuid('user_id')->index('index_user_id');

            $table->unique($columns = [
                'business_role_id',
                'user_id',
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
        Schema::dropIfExists('business_assigned_roles');
    }
}
