<?php

namespace App\Http\Controllers\Dashboard\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
    /**
     * ForgotPasswordController constructor.
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Display the form to request a password reset link.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLinkRequestForm()
    {
        return Response::view('dashboard.authentication.passwords.email');
    }

    /**
     * Send a reset link to the given user.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function sendResetLinkEmail(Request $request)
    {
        $data = $this->validate($request, [
            'email' => [
                'required',
                'email',
            ],
        ]);

        $response = Password::sendResetLink($data);

        if ($response === Password::RESET_LINK_SENT) {
            if ($request->expectsJson()) {
                return Response::json([
                    'message' => Lang::get($response),
                ]);
            }

            return Redirect::back()->with('status', Lang::get($response));
        }

        throw ValidationException::withMessages([
            'email' => Lang::get($response),
        ]);
    }
}
