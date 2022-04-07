<?php

namespace App\Http\Controllers\Api;

use App\Client;
use App\Http\Controllers\Controller;
use App\Http\Resources\User;
use App\Logics\UserRepository;
use Exception;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Laravel\Passport\Http\Controllers\HandlesOAuthErrors;

class UserController extends Controller
{
    use HandlesOAuthErrors;

    /**
     * UserController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api')->except('register');
    }

    /**
     * Register a new user.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function register(Request $request)
    {
        $client = Client::where('password_client', true)->where('revoked', false)->first();

        if (!$client instanceof Client) {
            throw new Exception('No available password grant clients were found.');
        }

        $user = UserRepository::store($request);

        $request->request->replace([
            'grant_type' => 'password',
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'username' => $user->email,
            'password' => $request->get('password'),
            'scope' => null,
        ]);

        return Route::dispatch($request->create(URL::route('api.v1.passport.token'), 'POST'));
    }

    /**
     * Show user profile.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \App\Http\Resources\User
     */
    public function showProfile(Request $request)
    {
        $user = Auth::user();

        $user->load('role');

        if ($request->get('with_businesses_owned')) {
            $user->load('businesses');
        }

        if ($request->get('with_businesses_managed')) {
            $user->businessesManaged();
        }

        return new User($user);
    }

    /**
     * Update the basic information of user.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \App\Http\Resources\User
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function updateBasicInformation(Request $request)
    {
        $user = Auth::user();

        $user = UserRepository::updateBasicInformation($request, $user);

        return new User($user);
    }

    /**
     * Update the email of user..
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \App\Http\Resources\User
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function updateEmail(Request $request)
    {
        $user = Auth::user();

        $user = UserRepository::updateEmail($request, $user);

        return new User($user);
    }

    /**
     * Update the phone number of user..
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \App\Http\Resources\User
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function updatePhoneNumber(Request $request)
    {
        $user = Auth::user();

        $user = UserRepository::updatePhoneNumber($request, $user);

        return new User($user);
    }

    /**
     * Update the password of user..
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \App\Http\Resources\User
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $user = UserRepository::updatePassword($request, $user);

        return new User($user);
    }

    public function setup(Request $request)
    {
        $user = Auth::user();

        if ($user->email_login_enabled) {
            App::abort(404);
        }

        $data = $this->validate($request, [
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($user->id, 'id'),
            ],
            'password' => [
                'required',
                'regex:/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9])(?!.*\s).{8,}$/',
            ],
        ], [
            'regex' => 'The :attribute must contain a minimum of 8 chracters, containing 1 upper case, 1 lower case, 1 number and 1 special character',
        ]);

        $user->email = $data['email'];
        $user->password = $data['password'];
        $user->email_login_enabled = true;
        $user->save();

        return new User($user);
    }
}
