<?php

namespace App\Logics;

use App\Enumerations\Gender;
use App\Events\User\EmailUpdated;
use App\Events\User\PasswordUpdated;
use App\Events\User\PhoneNumberUpdated;
use App\Events\User\Updated;
use App\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class UserRepository
{
    /**
     * Store a new user.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \App\User
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public static function store(Request $request) : User
    {
        $data = Validator::validate($request->all(), [
            'display_name' => [
                'required',
                'string',
                'max:64',
            ],
            'first_name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'last_name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'birth_date' => [
                'sometimes',
                'required',
                'date_format:Y-m-d',
            ],
            'gender' => [
                'sometimes',
                'required',
                new In(Gender::listConstants()),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users'),
            ],
            'phone_number' => [
                'nullable',
                'mobile_phone_number',
                Rule::unique('users'),
            ],
            'password' => [
                'required',
                'string',
                'min:8',
            ],
        ]);

        $user = DB::transaction(function () use ($data) {
            return User::create($data);
        }, 3);

        Event::dispatch(new Registered($user));

        return $user;
    }

    /**
     * Update the basic information of an existing user.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\User $user
     *
     * @return \App\User
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public static function updateBasicInformation(Request $request, User $user) : User
    {
        $data = Validator::validate($request->all(), [
            'display_name' => [
                'required',
                'string',
                'max:64',
            ],
            'first_name' => [
                'required',
                'string',
                'max:255',
            ],
            'last_name' => [
                'required',
                'string',
                'max:255',
            ],
            'birth_date' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'gender' => [
                'nullable',
                Rule::in(Gender::listConstants()),
            ],
        ]);

        $user = DB::transaction(function () use ($user, $data) : User {
            $user->update($data);

            return $user;
        }, 3);

        Event::dispatch(new Updated($user));

        return $user;
    }

    /**
     * Update the basic information of an existing user.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\User $user
     *
     * @return \App\User
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public static function updateEmail(Request $request, User $user) : User
    {
        $data = Validator::validate($request->all(), [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->getKey()),
            ],
        ]);

        $user = DB::transaction(function () use ($user, $data) : User {
            $user->update($data);

            return $user;
        }, 3);

        Event::dispatch(new EmailUpdated($user));

        return $user;
    }

    /**
     * Update the phone number of an existing user.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\User $user
     *
     * @return \App\User
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public static function updatePhoneNumber(Request $request, User $user) : User
    {
        $data = Validator::validate($request->all(), [
            'phone_number' => [
                'nullable',
                'string',
                'mobile_phone_number',
                Rule::unique('users')->ignore($user->getKey()),
            ],
        ]);

        $user = DB::transaction(function () use ($user, $data) : User {
            $user->update($data);

            return $user;
        }, 3);

        Event::dispatch(new PhoneNumberUpdated($user));

        return $user;
    }

    /**
     * Update the password of an existing user.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\User $user
     * @param array|null $rules
     * @param array $messages
     *
     * @return \App\User
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public static function updatePassword(
        Request $request, User $user, array $rules = null, array $messages = []
    ) : User {
        $data = Validator::validate($request->all(), $rules ?? [
                'password' => [
                    'required',
                    'string',
                    'min:8',
                ],
            ], $messages);

        $user = DB::transaction(function () use ($user, $data) : User {
            $user->update($data);

            return $user;
        }, 3);

        Event::dispatch(new PasswordUpdated($user));
        Auth::logoutOtherDevices($data['password']);

        return $user;
    }
}
