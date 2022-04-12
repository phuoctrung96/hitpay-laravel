<?php

namespace App\Http\Controllers\Dashboard\Auth;

use App\Actions\User\Partner\RegisterForm;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterPartnerRequest;
use App\Logics\BusinessRepository;
use App\Models\BusinessPartner;
use App\Notifications\NewPartnerRegistration;
use App\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class RegisterPartnerController extends Controller
{
    public function showRegistrationForm()
    {
        $actionData = RegisterForm::withData([])->process();

        $business_categories = $actionData['business_categories'] ?? [];
        $countries = $actionData['countries'] ?? [];

        return Response::view('dashboard.register-partner', compact('business_categories', 'countries'));
    }

    /**
     * @throws \ReflectionException
     * @throws \Throwable
     * @throws \Illuminate\Validation\ValidationException
     */
    public function register(RegisterPartnerRequest $request)
    {
        try {
            DB::beginTransaction();
            /** @var User $user */
            $user = User::create($request->only('display_name', 'email', 'password'));

            $user->businessPartner()->create($this->getBusinessPartnerAttributes($request));

            Event::dispatch(new Registered($user));
            Auth::login($user, true);

            BusinessRepository::store($request, Auth::user());

            User::superAdmins()->each(function(User $admin) use ($user) {
                $admin->notify(new NewPartnerRegistration($user));
            });

            DB::commit();

            $route = 'dashboard.home';

            if ($request->expectsJson()) {
                return Response::json([
                    'redirect_url' => URL::route($route),
                ]);
            }

            return Response::redirectToRoute($route);
        } catch (\Exception $exception) {
            DB::rollBack();

            throw $exception;
        }
    }

    private function getBusinessPartnerAttributes(RegisterPartnerRequest $request): array
    {
        $attributes = $request->only('website', 'services', 'platforms', 'short_description', 'special_offer');
        $attributes['logo_path'] = $request->file('logo')->store('public/partner-logo');
        $attributes['services'] = explode(',', $attributes['services']);
        if(in_array('Other', $attributes['services'])) {
            $attributes['services'][count($attributes['services']) - 1] .= ': ' . $request->input('other_service');
        }
        $attributes['platforms'] = explode(',', $attributes['platforms']);
        $attributes['referral_code'] = $this->makeReferralCode($request->input('display_name'));

        return $attributes;
    }

    private function makeReferralCode(string $name): string
    {
        $code = false;

        $generateCode = function (string $name) {
            $code = Str::substr(str_replace(' ', '', $name), 0, 4) . Str::random(6);
            $code = Str::upper($code);

            if(BusinessPartner::where('referral_code', $code)->exists()) {
                return false;
            }

            return $code;
        };

        while(!$code) {
            $code = $generateCode($name);
        }

        return $code;
    }
}
