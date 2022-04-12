<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Actions\Business\Settings\Verification\MoreConfirm;
use App\Actions\Business\Settings\Verification\Update;
use App\Actions\Exceptions\BadRequest;
use App\Business;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades;
use Illuminate\Support\Facades\Session;
use Validator;

class VerificationMoreConfirmController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request, Business $business, Business\Verification $verification = null)
    {
        Facades\Gate::inspect('update', $business)->authorize();

        if ($verification == null && !$request->has('verification')) {
            return Facades\Response::redirectToRoute('dashboard.business.verification.home', $business->getKey());
        }

        if (!$verification == null && $business->getKey() !== $verification->business_id) {
            throw new AuthorizationException;
        }

        try {
            MoreConfirm::withBusiness($business)
                ->data($request->all())
                ->withRequestFile($request)
                ->setVerification($verification)
                ->process();
        } catch (BadRequest $exception) {
            if ($request->wantsJson()) {
                return Facades\Response::json([
                    'message' => $exception->getMessage(),
                ], \Illuminate\Http\Response::HTTP_BAD_REQUEST);
            }

            return Facades\Response::redirectToRoute('dashboard.business.verification.home', [
                'business_id' => $business->getKey(),
            ])->with('error_message', $exception->getMessage());
        }

        Session::flash('success_message_completed', 'Your account verification has been completed. You can start accepting payments.');

        return route('dashboard.business.verification.home', $business->getKey());
    }
}
