<?php

namespace App\Logics\Business;

use App\Business;
use App\Business\PaymentProvider;
use App\Enumerations\Gender;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Exceptions\HitPayLogicException;
use App\Logics\ConfigurationRepository;
use Exception;
use HitPay\Business\Charge\Calculator;
use HitPay\Stripe\OAuth as StripeOAuth;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\OAuth\InvalidClientException;
use Stripe\Exception\OAuth\InvalidGrantException;
use Throwable;

class PaymentProviderRepository
{
    /**
     * Add a new payment provider.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param string $target
     *
     * @return \App\Business\PaymentProvider
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public static function store(Request $request, Business $business, string $target) : PaymentProvider
    {
        if ($target === PaymentProviderEnum::STRIPE_SINGAPORE || $target === PaymentProviderEnum::STRIPE_MALAYSIA) {
            $data = Validator::validate($request->all(), [
                'state' => [
                    'required',
                    'string',
                ],
                'scope' => [
                    'required',
                    'in:read_write',
                ],
                'code' => [
                    'required',
                    'string',
                ],
            ]);
        } else {
            throw new HitPayLogicException('Unsupported payment provider requested.');
        }
        // todo have to differentiate what is the payment provider is.
        $state = Cache::pull('stripe_oauth:'.$data['state']);

        if ($state !== $business->getKey()) {
            throw ValidationException::withMessages([
                'state' => 'The state is invalid or expired.', // TODO - Localization
            ]);
        }

        try {
            /**
             * @var $token \Stripe\StripeObject
             * @var $account \Stripe\Account
             */
            [
                $token,
                $account,
            ] = StripeOAuth::new($business->payment_provider)->authorizeAccount($data['code']);

            if (strtolower($account->country) !== $business->country) {
                throw new AuthorizationException('The country of the Stripe account doesn\'t match the business.');
            } elseif (!$account->charges_enabled) {
                throw new AuthorizationException('The authorized Stripe account isn\'t charges enabled.');
            }

            return DB::transaction(function () use (
                $business, $token, $account
            ) : PaymentProvider {

                $accountData = $account->toArray();

                // We want the country codes to be in lower case. We assumes that Stripe never change the structure of
                // the account object returned.

                if (isset($accountData['country'])) {
                    $accountData['country'] = strtolower($accountData['country']);
                }

                if (isset($accountData['support_address']['country'])) {
                    $accountData['support_address']['country'] = strtolower($accountData['support_address']['country']);
                }

                return $business->paymentProviders()->create([
                    'payment_provider' => $business->payment_provider,
                    'payment_provider_account_type' => 'standard',
                    'payment_provider_account_id' => $account->id,
                    'onboarding_status' => 'success',
                    'stripe_publishable_key' => $token->stripe_publishable_key,
                    'token_type' => $token->token_type,
                    'access_token' => $token->access_token,
                    'refresh_token' => $token->refresh_token,
                    'token_scopes' => $token->scope,
                    'data' => $accountData,
                ]);
            }, 3);
        } catch (InvalidGrantException $exception) {

            if ($exception->getStripeCode() === 'invalid_grant') {

                throw ValidationException::withMessages([
                    'code' => 'Invalid authorization code.',
                ]);
            }

            throw $exception;
        } catch (Exception|Throwable $exception) {

            try {

                StripeOAuth::newWithClientId($business->payment_provider)->deauthorizeAccount($account->id);
            } catch (InvalidClientException $exception) {

                if ($exception->getStripeCode() !== 'invalid_client') {
                    throw $exception;
                }
            }

            throw $exception;
        }
    }

    /**
     * Update an existing payment provider.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business\PaymentProvider $paymentProvider
     *
     * @return \App\Business\PaymentProvider
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public static function update(Request $request, PaymentProvider $paymentProvider) : PaymentProvider
    {
        $data = Validator::validate($request->all(), [
            'name' => [
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
            'phone_number' => [
                'nullable',
                'phone_number',
            ],
        ]);

        // todo what the fuck is this
        $paymentProvider = DB::transaction(function () use (
            $paymentProvider, $data
        ) : PaymentProvider {
            $paymentProvider->update($data);

            return $paymentProvider;
        }, 3);

        return $paymentProvider;
    }

    /**
     * Remove an existing payment provider.
     *
     * @param \App\Business\PaymentProvider $paymentProvider
     *
     * @return bool|null
     * @throws \Throwable
     */
    public static function delete(PaymentProvider $paymentProvider) : ?bool
    {
        return DB::transaction(function () use ($paymentProvider) : ?bool {
            $deleted = $paymentProvider->delete();

            try {
                StripeOAuth::newWithClientId($paymentProvider->payment_provider)
                    ->deauthorizeAccount($paymentProvider->payment_provider_account_id);
            } catch (InvalidClientException $exception) {
                if ($exception->getStripeCode() !== 'invalid_client') {
                    throw $exception;
                }
            }

            return $deleted;
        }, 3);
    }

    /**
     * Get the rate for a business.
     *
     * @param \App\Business $business
     * @param string $currency
     * @param string $method
     * @param string $channel
     * @param string $paymentProvider
     * @param bool $eligibleForDestinationCharge
     *
     * @return \HitPay\Business\Charge\Calculator
     * @throws \App\Exceptions\HitPayLogicException
     */
    public static function rate(
        Business $business, string $currency, string $method, string $channel, string $paymentProvider = null,
        bool $eligibleForDestinationCharge = false
    ) : Calculator {
        if (is_null($paymentProvider)) {
            $paymentProvider = $business->payment_provider;
        }

        $paymentProvider = $business->paymentProviders()->where('payment_provider', $paymentProvider)->firstOrFail();

        if ($business->currency === $currency && $eligibleForDestinationCharge) {
            $rates = $paymentProvider->rates()->where('method', $method)->where('channel', $channel)->get();

            foreach ($rates->sortByDesc('starts_at') as $rate) {
                if ($rate->starts_at && !$rate->starts_at->isPast()) {
                    continue;
                } elseif ($rate->ends_at && $rate->ends_at->isPast()) {
                    continue;
                }

                return new Calculator($paymentProvider, $currency, $method, Calculator::DESTINATION_CHARGE,
                    $rate->fixed_amount, $rate->percentage, $channel);
            }

            $default = ConfigurationRepository::get($paymentProvider->payment_provider.'_destination_charge_rate');
            // todo default should include all method.
            if ($default) {
                return new Calculator($paymentProvider, $currency, $method, Calculator::DESTINATION_CHARGE,
                    $default['fixed_amount'], $default['percentage'], $channel);
            } elseif ($paymentProvider->payment_provider === PaymentProviderEnum::STRIPE_SINGAPORE) {
                return new Calculator($paymentProvider, $currency, $method, Calculator::DESTINATION_CHARGE, 60, 0.034,
                    $channel);
            }

            throw new HitPayLogicException(sprintf('The rate is not set properly for payment method [%s].',
                $paymentProvider->id));
        }

        $default = ConfigurationRepository::get($paymentProvider->payment_provider.'_direct_charge_rate');

        if ($default) {
            return new Calculator($paymentProvider, $currency, $method, Calculator::DIRECT_CHARGE,
                $default['fixed_amount'], $default['percentage'], $channel);
        }

        return new Calculator($paymentProvider, $currency, $method, Calculator::DIRECT_CHARGE, 0, 0.01, $channel);
    }
}
