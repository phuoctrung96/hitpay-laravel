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
use Illuminate\Support\Facades\Response;

class UserController extends Controller
{
    public function index(Request $request, Business $business, BusinessUserPermissionsService $businessUserPermissionsService)
    {
        $this->authorize('list', [BusinessUser::class, $business]);

        $currentBusinessUser = $businessUserPermissionsService->getBusinessUser(\Auth::user(), $business);

        $businessUsers = $business->businessUsers()
            ->orderBy('created_at')
            ->with('user.role', 'role')
            ->withoutGlobalScope('active')
            ->get()
            ->filter(function(BusinessUser $businessUser) use ($business) {
                $isSuperAdmin = $businessUser->user->isSuperAdmin();
                return !$isSuperAdmin || ($isSuperAdmin && $business->user_id == $businessUser->user_id);
            });


        $roles = Role::business()->forInvitedUsers()
            ->when($currentBusinessUser->isAdmin(), function($query) {
                return $query->whereNotIn('id', [Role::owner()->id, Role::admin()->id]);
            })
            ->get();

        $restrictions = $business->rolesRestrictions;

        if($request->wantsJson()) {
            return Response::json(compact('business', 'roles', 'businessUsers'));
        }

        return Response::view('dashboard.business.users', compact('business', 'roles', 'businessUsers', 'currentBusinessUser', 'restrictions'));
    }

    public function invite(Request $request, Business $business)
    {
        $this->authorize('manage', [BusinessUser::class, $business]);

        $this->validate($request, [
            'email' => 'required|email',//TODO: check that user is not invited to this business
            'role_id' => 'required|exists:roles,id'
        ]);

        if(!$user = User::where('email', $request->input('email'))->first()) {
            $user = new User();
            $user->email = $request->input('email');
            $user->save();
        }

        $businessUser = $business->businessUsers()->create([
            'user_id' => $user->id,
            'role_id' => $request->input('role_id')
        ]);

        $user->notify(new NotifyUserInvitation($business));
        $admins = $business->businessUsers()->where('role_id', Role::admin()->id)->get();
        foreach ($admins as $admin) {
            $admin->user->notify(new NotifyUserInvitation($business));
        }

        return Response::json(['data' => $businessUser]);
    }

    public function update(Request $request, Business $business, $id)
    {
        $this->authorize('manage', [BusinessUser::class, $business]);

        $role = Role::query()
            ->business()
            ->findOrFail($request->input('role_id'));

        $businessUser = $business->businessUsers()
            ->withoutGlobalScope('active')
            ->findOrFail($id);
        $businessUser->update([
            'role_id' => $role->id,
        ]);

        return Response::json(['data' => $businessUser]);
    }

    public function detach(Business $business, $id)
    {
        $this->authorize('manage', [BusinessUser::class, $business]);

        $business->businessUsers()
            ->withoutGlobalScope('active')
            ->findOrFail($id)->delete();

        $businessUsers = $business->businessUsers()->with('user', 'role')->get();

        return Response::json(compact('businessUsers'));
    }
}
