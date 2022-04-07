<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\Business\Referral\Retrieve;
use App\Actions\Business\Referral\SendInvitation;
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

        $actionData = Retrieve::withBusiness($business)->process();

        $amount = $actionData['amount'] ?? null;

        return Response::view('dashboard.business-referral-program.index', compact('business', 'amount'));
    }

    /**
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function sendInvite(Request $request, Business $business)
    {
        $this->authorizeForUser(Auth::user(), 'view', $business);

        SendInvitation::withBusiness($business)->setEmailInvitation($request->email)->process();

        return back()->with('success_message', 'Invitation was successfully sent.');
    }
}
