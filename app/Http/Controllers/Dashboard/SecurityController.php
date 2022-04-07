<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Logics\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class SecurityController extends Controller
{
    /**
     * UserController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function showHomepage()
    {
        $user = Auth::user();

        return Response::view('dashboard.security', compact('user'));
    }

    public function secret()
    {
    }

    public function disableSecret()
    {
    }

    public function enableSecret()
    {
    }

    public function getSecret()
    {
    }

    public function showEmailForm()
    {
    }

    public function updateEmail()
    {
    }

    public function getFailedAuthRecords()
    {
    }

    /**
     * Show update password page.
     *
     * @return \Illuminate\Http\Response
     */
    public function showPasswordForm()
    {
        $user = Auth::user();

        return Response::view('dashboard.authentication.password', compact('user'));
    }

    /**
     * Update password.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $data = $this->validate($request, [
            'current_password' => [
                'required',
                'string',
                'password',
            ],
            'new_password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ], [
            'current_password.password' => 'The current password is incorrect.',
        ]);

        $user->password = $data['new_password'];

        $user->save();

        Auth::logoutOtherDevices($data['new_password']);

        return Response::json([
            'message' => 'Your password has been successfully updated.',
        ]);
    }

    public function getSessionRecords()
    {
    }

    public function destroySession()
    {
    }
}
