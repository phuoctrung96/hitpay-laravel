<?php

namespace App\Http\Controllers\Dashboard;

use App\Events\User\Updated;
use App\Http\Controllers\Controller;
use App\Http\Resources\User;
use App\Logics\UserRepository;
use App\User as UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * UserController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function showMigratedMessage()
    {
        $user = Auth::user();

        if ($user->email_login_enabled) {
            return Response::redirectToRoute('dashboard.user.profile');
        }

        return Response::view('dashboard.user-welcome', compact('user'));
    }

    /**
     * Show profile page.
     *
     * @return \Illuminate\Http\Response
     */
    public function showProfilePage()
    {
        $user = Auth::user();

        if ($user->email_login_enabled) {
            return Response::view('dashboard.user', compact('user'));
        }

        return Response::view('dashboard.user-setup', compact('user'));
    }

    /**
     * Update basic information.
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
     * Setup account.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public function setupAccount(Request $request)
    {
        $user = Auth::user();

        $data = $this->validate($request, [
            'display_name' => [
                'required',
                'string',
                'max:64',
            ],
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->getKey()),
            ],
            'password' => [
                'required',
                'min:8',
                'confirmed',
            ]
        ]);

        $data['email_login_enabled'] = true;

        $user = DB::transaction(function () use ($user, $data) : UserModel {
            $user->update($data);

            return $user;
        }, 3);

        Event::dispatch(new Updated($user));

        return Response::json([
            'success' => true,
        ]);
    }
}
