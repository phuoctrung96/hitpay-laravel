<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessRoleGrantedPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_role_granted_permissions', function (Blueprint $table) {
            $table->uuid('business_role_id');
            $table->string('permission', 32);

            $table->unique($columns = [
                'business_role_id',
                'permission',
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
        Schema::dropIfExists('business_role_granted_permissions');
    }
}
