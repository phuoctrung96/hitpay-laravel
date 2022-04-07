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

//    public function invite(Request $request, Business $business)
//    {
//        $this->authorize('manage', [BusinessUser::class, $business]);
//
//        $this->validate($request, [
//            'email' => 'required|email',//TODO: check that user is not invited to this business
//            'role_id' => 'required|exists:roles,id'
//        ]);
//
//        if(!$user = User::where('email', $request->input('email'))->first()) {
//            $user = new User();
//            $user->email = $request->input('email');
//            $user->save();
//        }
//
//        $businessUser = $business->businessUsers()->create([
//            'user_id' => $user->id,
//            'role_id' => $request->input('role_id')
//        ]);
//
//        $user->notify(new NotifyUserInvitation($business));
//        $admins = $business->businessUsers()->where('role_id', Role::admin()->id)->get();
//        foreach ($admins as $admin) {
//            $admin->user->notify(new NotifyUserInvitation($business));
//        }
//
//        return Response::json(['data' => $businessUser]);
//    }
//
//    public function update(Request $request, Business $business, $id)
//    {
//        $this->authorize('manage', [BusinessUser::class, $business]);
//
//        $role = Role::query()
//            ->business()
//            ->findOrFail($request->input('role_id'));
//
//        $businessUser = $business->businessUsers()
//            ->withoutGlobalScope('active')
//            ->findOrFail($id);
//        $businessUser->update([
//            'role_id' => $role->id,
//        ]);
//
//        return Response::json(['data' => $businessUser]);
//    }
//
//    public function detach(Business $business, $id)
//    {
//        $this->authorize('manage', [BusinessUser::class, $business]);
//
//        $business->businessUsers()
//            ->withoutGlobalScope('active')
//            ->findOrFail($id)->delete();
//
//        $businessUsers = $business->businessUsers()->with('user', 'role')->get();
//
//        return Response::json(compact('businessUsers'));
//    }
}
