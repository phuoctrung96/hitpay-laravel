<?php

namespace App\Http\Controllers\Dashboard\Auth;

use App\Business\BusinessUser;
use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Encryption\DecryptException;

class RegisterCompleteController extends Controller
{
    /**
     * RegisterController constructor.
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Show the application registration form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showRegistrationForm(Request $request, $hash)
    {
        try {
            $id = decrypt($hash);
        }catch (DecryptException $e){
            App::abort(403, 'Hash is not encrypted');
        }

        $user = User::query()
            ->whereNull('password')
            ->findOrFail($id);

        $email = $user->email;

        return Response::view('dashboard.register-complete', compact('email', 'hash'));
    }

    /**
     * Handle a registration request for the application.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function register(Request $request, $hash)
    {
        $user = User::query()
            ->whereNull('password')
            ->findOrFail(decrypt($hash));

        $data = $this->validate($request, [
            'display_name' => [
                'required',
                'string',
                'max:255',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
            ],
        ]);

        $user->update($data);

        Event::dispatch(new Registered($user));
        Auth::login($user, true);

        $route = 'dashboard.home';

        if ($request->expectsJson()) {
            return Response::json([
                'redirect_url' => URL::route($route),
            ]);
        }

        return Response::redirectToRoute($route);
    }
}
