<?php

namespace App\Http\Controllers\Dashboard;

use App\Business;
use App\Enumerations\Business\Wallet\Type;
use App\Http\Controllers\Controller;
use App\Notifications\BusinessReferralProgramInviteNotification;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class BusinessReferralProgramController extends Controller
{
    public function index(Business $business)
    {
        $user = Auth::user();
        $this->authorizeForUser($user, 'view', $business);

        $wallet = $business->wallet(Type::AVAILABLE, 'SGD');
        $amount = number_format($wallet->transactions()->where('event', 'business_referral_commission')->sum('amount') / 100, 2);

        return Response::view('dashboard.business-referral-program.index', compact('business', 'amount'));
    }

    public function sendInvite(Request $request, Business $business)
    {
        $this->authorizeForUser(Auth::user(), 'view', $business);

        $this->validate($request, [
            'email' => 'required|email'
        ]);

        $user = new User();
        $user->email = $request->input('email');
        $user->notify(new BusinessReferralProgramInviteNotification($business));



        return back()->with('success_message', 'Invitation was successfully sent.');
    }
}
