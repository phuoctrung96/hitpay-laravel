<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Laravel\Passport\Passport;

class HomeController extends Controller
{
    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function showHomepage()
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            if($user->hasPendingBusinessInvitations()) {
                return Response::redirectToRoute('dashboard.pending-invitations.index', [
                    'src' => 'dashboard',
                ]);
            }

            if ($user->businesses()->count() === 0) {
                //TODO: what role should be assigned to users when it was invited to business as manager
                return Response::redirectToRoute('dashboard.business.create', [
                    'src' => 'dashboard',
                ]);
            }

            if ($user->businesses()->count() > 1) {
                return Response::redirectToRoute('dashboard.choose-business');
            }

            return Response::redirectToRoute('dashboard.business.home', $user->businesses()->first()->getKey());
        }

        return Response::view('dashboard.authentication.pre-auth');
    }
}
