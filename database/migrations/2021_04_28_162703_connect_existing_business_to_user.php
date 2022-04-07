<?php

use App\Business\BusinessUser;
use App\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConnectExistingBusinessToUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $role = \App\Role::owner();
        DB::table('business_assigned_roles')
            ->join('business_roles', 'business_roles.id', '=', 'business_assigned_roles.business_role_id')
            ->select('business_roles.business_id', 'business_assigned_roles.user_id')
            ->get()
            ->each(function($row) use($role) {
                /** @var Business $business */
                $business = App\Business::find($row->business_id);
                $user = \App\User::find($row->user_id);
                if($business && $user) {
                    $business->businessUsers()->create([
                        'user_id' => $user->id,
                        'role_id' => $role->id
                    ]);
                }
            });

        \App\User::query()->get()->each(function(\App\User $user) use($role) {
            foreach ($user->businessesOwned as $business) {
                if(!$business->users()->where('users.id', $user->id)->exists()) {
                    $business->businessUsers()->create([
                        'user_id' => $user->id,
                        'role_id' => $role->id
                    ]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('business_user')
            ->delete();
    }
}
