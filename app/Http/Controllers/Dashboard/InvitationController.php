<?php


namespace App\Http\Controllers\Dashboard;


use App\Business;
use App\Business\BusinessUser;
use App\Http\Controllers\Controller;

class InvitationController extends Controller
{
    public function index()
    {
        $user = \Auth::user();
        $pendingInvitations = $user->pendingInvitations()->with('business', 'role')->get();
        if(!$pendingInvitations->count()) {
            return redirect('/');
        }

        return view('dashboard.pending-invitations', compact('pendingInvitations'));
    }



    public function accept($id)
    {
        $businessUser = \Auth::user()->pendingInvitations()->findOrFail($id);
        $businessUser->update(['invite_accepted_at' => now()]);

        return back();
    }

    public function decline($id)
    {
        $businessUser = \Auth::user()->pendingInvitations()->findOrFail($id);
        $businessUser->delete();

        return back();
    }
}
