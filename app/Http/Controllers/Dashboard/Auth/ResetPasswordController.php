<?php

namespace App\Http\Controllers\Dashboard\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    /**
     * ResetPasswordController constructor.
     */
    public function __construct()
    {
        Auth::logout();
        $this->middleware('guest');
    }

    /**
     * Display the password reset view for the given token.
     *
     * If no token is present, display the link request form.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $token
     *
     * @return \Illuminate\Http\Response
     */
    public function showResetForm(Request $request, string $token)
    {
        // todo show oauth field if user is set.
        return Response::view('dashboard.authentication.passwords.reset', [
            'token' => $token,
            'email' => $request->get('email'),
        ]);
    }

    /**
     * Reset the given user's password.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function reset(Request $request)
    {
        $data = $this->validate($request, [
            'token' => [
                'required',
            ],
            'email' => [
                'required',
                'email',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);

        $data['password_confirmation'] = $request->get('password_confirmation');

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $response = Password::broker()->reset($data, function (User $user, string $password) {
            // todo ask for 2fa if enabled before save password.
            $user->password = $password;
            $user->setRememberToken(Str::random(60));
            $user->save();

            Event::dispatch(new PasswordReset($user));
            Auth::guard()->login($user);

            Auth::logoutOtherDevices($password);
        });

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.

        if ($response === Password::PASSWORD_RESET) {
            if ($request->expectsJson()) {
                return Response::json([
                    'redirect_url' => URL::route('dashboard.home'),
                ]);
            }

            return Response::redirectToRoute('dashboard.home')->with('status', Lang::get($response));
        }

        // check this shit
        return Redirect::back()->withInput($request->only('email'))->withErrors([
            'email' => Lang::get($response),
        ]);
    }
}
