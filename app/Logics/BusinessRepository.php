<?php

namespace App\Logics;

use App\Actions\Business\BasicDetails\UpdateStripeAccount;
use App\Business;
use App\Business\BusinessReferral;
use App\Enumerations\Business\PaymentMethodType;
use App\Enumerations\Business\Type;
use App\Enumerations\CountryCode;
use App\Enumerations\PaymentProvider;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Events\Business\Created;
use App\Events\Business\Updated;
use App\Models\BusinessPartner;
use App\Notifications\RegistrationInviteAccepted;
use App\Role;
use App\User;
use Carbon\Carbon;
use Exception;
use HitPay\Stripe\CustomAccount\Create;
use HitPay\Stripe\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class BusinessRepository
{
    /**
     * Store a new business.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\User $user
     *
     * @return \App\Business
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public static function store(Request $request, User $user) : Business
    {
        $data = Validator::validate($request->all(), [
            'identifier' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('businesses'),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'display_name' => [
                'nullable',
                'string',
                'max:64',
            ],
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
            ],
            'country' => [
                'required',
                'string',
                Rule::in([ CountryCode::MALAYSIA, CountryCode::SINGAPORE ]),
            ],
            'phone_number' => [
                'nullable',
            ],
            'street' => [
                'required_with:city,state,postal_code',
                'string',
                'max:255',
            ],
            'city' => [
                'required_with:street,state,postal_code',
                'string',
                'max:255',
            ],
            'state' => [
                'required_with:street,city,postal_code',
                'string',
                'max:255',
            ],
            'postal_code' => [
                'required_with:street,city,state',
                'string',
                'max:16',
            ],
            'introduction' => [
                'nullable',
                'string',
                'max:65536',
            ],
            'statement_description' => [
                'nullable',
                'string',
                'max:22',
            ],
            'founding_date' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'business_type' => [
                'required',
                Rule::in(array('company', 'individual')),
            ],
            'website' => [
                'required',
                'regex:/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/',
                'max:255',
            ],
            'referred_channel' => [
                'required',
            ],
            'merchant_category' => [
                'required',
                'string'
            ],
        ]);

        $website = $data['website'];

        if (!Facades\URL::isValidUrl($website)) {
            // try to use https
            $website = "https://{$website}";

            if (!Facades\URL::isValidUrl($website)) {
                // if still invalid
                ValidationException::withMessages([
                    'website' => 'Invalid website',
                ]);
            }
        }

        $data['website'] = $website;

        $data['id'] = Str::orderedUuid()->toString();
        $customer = null; // customer of stripe

        if ($user->businessPartner) { // handle partner
            $data['business_type'] = Type::PARTNER;

            if ($data['country'] === CountryCode::MALAYSIA) {
                $data['payment_provider'] = Customer::getStripePlatformByCountry($data['country']);

                $customer = Customer::newByCountry($data['country'])->create('business_id:'.$data['id']);

                $data['payment_provider_customer_id'] = $customer->id;
            } else {
                // singapore partner should create payment provider of PayNow
                $data['payment_provider'] = '';
            }
        } else {
            $data['payment_provider'] = Customer::getStripePlatformByCountry($data['country']);

            $customer = Customer::newByCountry($data['country'])->create('business_id:'.$data['id']);

            $data['payment_provider_customer_id'] = $customer->id;
        }

        try {
            /** @var \App\Business $business */
            $business = DB::transaction(function () use ($user, $data, $request) : Business {
                $code = $request->session()->get('business_referral');
                if($code && $businessReferral = BusinessReferral::findByCode($code)) {
                    $data['referred_by_id'] = $businessReferral->id;
                }

                /** @var Business $business */
                $business = $user->businessesOwned()->create($data);
                $business->businessReferral()->create([
                    'id' => Str::uuid(),
                    'code' => static::generateBusinessReferralCode($business),
                    'country' => $business->country,
                    'starts_at' => Carbon::now(),
                    'referral_fee' => config('business.referral.default_fee')
                ]);

                if(!empty($data['referred_by_id']) && isset($businessReferral) && $businessReferral instanceof BusinessReferral) {
                    $businessReferral->business->notify(new RegistrationInviteAccepted($business));
                }

                if($partner = BusinessPartner::findByCode($request->session()->get('partner_referral'))) {
                    $partner->businesses()->attach($business->id, [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $request->session()->forget('partner_referral');
                }

                $business->businessUsers()->create([
                    'user_id' => $user->id,
                    'role_id' => Role::owner()->id,
                    'invite_accepted_at' => now(),
                ]);

                // TODO - 2020-01-26
                //
                // Use localization when second language is enabled.

                $tax = $business->taxes()->create([
                    'name' => 'Standard',
                    'applies_locally' => true,
                    'applies_overseas' => true,
                    'rate' => 0,
                ]);
                $shipping = $business->shippings()->make([
                    'calculation' => 'flat',
                    'name' => 'Standard',
                    'rate' => 0,
                ]);

                $shipping->tax()->associate($tax);
                $shipping->save();
                $shipping->setCountries([
                    $business->country,
                ]);
                $business->productCategories()->create([
                    'name' => 'General',
                ]);

                if (
                    $business->country === CountryCode::SINGAPORE ||
                    $business->country === CountryCode::MALAYSIA
                ) {
                    // If the business is Singapore based (soon other licensed countries) we will create a custom account
                    // for the business.
                    //

                    if ($business->shouldHaveStripeCustomAccount()) {
                        try {
                            Create::new($business->payment_provider)->setBusiness($business)
                                ->setClientIp($request->ip())
                                ->setClientUserAgent($request->userAgent())
                                ->handle();
                        } catch (\Exception $exception) {
                            Facades\Log::info('error when create business custom connect: ' . $exception->getMessage());
                            throw $exception;
                        }
                    }

                    if ($business->country === CountryCode::MALAYSIA) {
                        /*try {
                            $createCognitoFlow = new \HitPay\Verification\Cognito\FlowSession\Create();
                            $createCognitoFlow->setBusiness($business)->handle();
                        } catch (\Exception $exception) {
                            // can be skipped, this func for passing kyc (first_name, last name, phone)
                        }*/
                    }
                }

                return $business;
            }, 3);

            if($user->businessPartner) {
                $user->businessPartner->business_id = $business->id;
                $user->businessPartner->save();
            }
        } catch (Exception|Throwable $exception) {
            if ($customer !== null) {
                $customer->delete();
            }

            throw $exception;
        }

        Event::dispatch(new Created($business));

        return $business;
    }

    /**
     * Update an existing business.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Business
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public static function update(Request $request, Business $business) : Business
    {
        $data = Validator::validate($request->all(), [
            'identifier' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('businesses')->ignore($business->getKey()),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'display_name' => [
                'nullable',
                'string',
                'max:64',
            ],
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
            ],
            'phone_number' => [
                'nullable',
            ],
            'street' => [
                'nullable',
                'string',
                'max:255',
            ],
            'city' => [
                'nullable',
                'string',
                'max:255',
            ],
            'state' => [
                'nullable',
                'string',
                'max:255',
            ],
            'postal_code' => [
                'nullable',
                'string',
                'max:16',
            ],
            'introduction' => [
                'nullable',
                'string',
                'max:65536',
            ],
            'statement_description' => [
                'nullable',
                'string',
                'max:22',
            ],
            'founding_date' => [
                'nullable',
                'date_format:Y-m-d',
            ],
        ]);

        $business = DB::transaction(function () use ($business, $data) : Business {
            $business->update($data);

            return $business;
        }, 3);

        UpdateStripeAccount::withBusiness($business)->process();

        Event::dispatch(new Updated($business));

        return $business;
    }

    /**
     * Update identifier of an existing business.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Business
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public static function updateIdentifier(Request $request, Business $business) : Business
    {
        $data = Validator::validate($request->all(), [
            'identifier' => [
                'required',
                'string',
                'max:32',
                Rule::unique('businesses')->ignore($business->getKey()),
            ],
        ]);

        $business = DB::transaction(function () use ($business, $data) : Business {
            $business->update($data);

            return $business;
        }, 3);

        Event::dispatch(new Updated($business));

        return $business;
    }

    /**
     * "Remove" a standard Stripe account of an existing business.
     *
     * @param  \App\Business  $business
     * @param  string  $paymentProvider
     *
     * @return \App\Business
     * @throws \Exception
     */
    public static function removeStripePaymentProvider(Business $business, string $paymentProvider) : Business
    {
        if (!in_array($paymentProvider, [
            PaymentProviderEnum::STRIPE_SINGAPORE,
            PaymentProviderEnum::STRIPE_MALAYSIA,
        ])) {
            throw new Exception('The given payment provider is invalid.');
        }

        $paymentProvider = $business->paymentProviders()
            ->where('payment_provider', $paymentProvider)
            ->where('payment_provider_account_type', 'standard')
            ->orderBy('payment_provider')
            ->first();

        if ($paymentProvider instanceof Business\PaymentProvider) {
            $paymentProvider->payment_provider = $paymentProvider->payment_provider.'_'.microtime(true);
            $paymentProvider->save();

            $business->gatewayProviders()->get()->each(function (Business\GatewayProvider $gatewayProvider) {
                $methods = $gatewayProvider->methods ? json_decode($gatewayProvider->methods) : [];

                if (!is_array($methods)) {
                    $methods = json_decode($gatewayProvider->methods, true);
                }

                $key = array_search(PaymentMethodType::CARD, $methods);

                if ($key === false) {
                    return;
                }

                unset($methods[$key]);

                $gatewayProvider->methods = count($methods) ? json_encode($methods) : null;
                $gatewayProvider->save();
            });
        }

        return $business;
    }

    /**
     * "Remove" the current Singapore Stripe account of an existing business.
     *
     * @param  \App\Business  $business
     *
     * @return \App\Business
     * @throws \Exception
     */
    public static function removeStripeSingaporeAccount(Business $business) : Business
    {
        return self::removeStripePaymentProvider($business, PaymentProviderEnum::STRIPE_SINGAPORE);
    }

    private static function generateBusinessReferralCode(Business $business): string
    {
        $code = false;

        $generateCode = function (string $name) {
            $firstPart = Str::substr(str_replace(' ', '', $name), 0, 5);
            $secondPart = Str::random(5 + (5-strlen($firstPart)));
            $code = Str::upper($firstPart . $secondPart);

            if (BusinessReferral::where('code', $code)->exists()) {
                return false;
            }

            return $code;
        };

        while (!$code) {
            $code = $generateCode($business->name);
        }

        return $code;
    }
}
