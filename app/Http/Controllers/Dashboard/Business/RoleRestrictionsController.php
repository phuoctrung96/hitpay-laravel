<?php


namespace App\Http\Controllers\Dashboard\Business;


use App\Business;
use App\Business\BusinessUser;
use App\Http\Controllers\Controller;
use App\Notifications\Business\NotifyUserInvitation;
use App\Role;
use App\Services\BusinessUserPermissionsService;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;

class RoleRestrictionsController extends Controller
{
    public function update(Request $request, Business $business)
    {
        Gate::inspect('canRestrictRoles', $business)->authorize();

        $data = $request->validate([
            'cashier@refund' => 'required|bool',
        ]);

        $business_id = $business->getKey();

        foreach ($data as $index => $value) {
            if (!$value) {
                [$role, $restriction] = explode('@', $index);

                $roleRestrictions[] = compact('business_id', 'role', 'restriction');
            }
        }

        DB::beginTransaction();

        $business->rolesRestrictions()->delete();

        if (isset($roleRestrictions)) {
            $business->rolesRestrictions()->insert($roleRestrictions);

            $roleRestrictions = $business->roleRestrictions;
        }

        DB::commit();

        return Response::json($roleRestrictions ?? []);
    }
}
