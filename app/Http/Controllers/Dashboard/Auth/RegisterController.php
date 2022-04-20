<?php

namespace App\Http\Controllers\Dashboard\Auth;

use App\Actions\User\Register\RegisterForm;
use App\Business;
use App\Enumerations\BusinessPartnerStatus;
use App\Models\BusinessPartner;
use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class RegisterController extends Controller
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
    public function showRegistrationForm(Request $request)
    {
        $userData = cache()->get('xero_data_' . session()->getId());
        $partnerReferral = $request->input('partner_referral', '');
        $businessReferral = $request->input('referral_code', '');

        $registerData = RegisterForm::withRequest($request)->process();

        return Response::view('dashboard.register', compact(
            'userData', 'partnerReferral',
            'businessReferral'
        ));
    }

    /**
     * Handle a registration request for the application.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function register(Request $request)
    {
        $data = $this->validate($request, [
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
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'password' => [
                'required',
                'string',
                'min:8',
            ],
        ]);

        $data['xero_data'] = cache()->get('xero_data_' . session()->getId());

        $data['display_name'] = $data['first_name'] . ' ' . $data['last_name'];

        $user = User::create($data);

        Event::dispatch(new Registered($user));
        Auth::login($user, true);

        if ($request->get('src') === 'login') {
            $extra = [
                'src' => 'registration',
            ];

            $route = 'dashboard.business.create';
        } else {
            $extra = [
                'src' => 'registration',
            ];

            $route = 'dashboard.business.create';
        }

        if($request->has('partner_referral')) {
            $request->session()->put('partner_referral', $request->get('partner_referral'));
        }

        if($request->has('business_referral')) {
            $request->session()->put('business_referral', $request->get('business_referral'));
        }

        if ($request->expectsJson()) {
            return Response::json([
                'redirect_url' => URL::route($route, $extra ?? []),
            ]);
        }

        return Response::redirectToRoute($route, $extra ?? []);
    }
}
