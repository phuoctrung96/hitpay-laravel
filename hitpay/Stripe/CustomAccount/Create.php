<?php

namespace HitPay\Stripe\CustomAccount;

use App\Business\PaymentProvider;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Enumerations\OnboardingStatus;
use App\Logics\ConfigurationRepository;
use Illuminate\Support\Facades;
use Stripe;
use Throwable;

class Create extends CustomAccount
{
    /**
     * Create Stripe Account.
     *
     * This is an object representing a Stripe account. You can retrieve it to see properties on the account like its
     * current e-mail address or if the account is enabled yet to make live charges.
     *
     * Read more : https://stripe.com/docs/api/accounts/create
     *
     * @param  bool  $useStripeOnboarding
     *
     * @return \Illuminate\Http\RedirectResponse|\Stripe\Account
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\GeneralException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    public function handle(bool $useStripeOnboarding = false)
    {
        $paymentProvider = $this->getPaymentProvider(false);

        if ($paymentProvider !== null) {
            throw $this->exception("The business is already having a custom account (Stripe ID : {$paymentProvider->payment_provider_account_id}).");
        }

        // 'capabilities'
        // --------------
        // Each key of the dictionary represents a capability, and each capability maps to its settings (e.g. whether
        // it has been requested or not). Each capability will be inactive until you have provided its specific
        // requirements and Stripe has verified them. An account may have some of its requested capabilities be
        // active and some be inactive.
        //
        // TODO - KEEP IN MIND - Not all account will have same capabilities.
        $service_agreement = $this->business->isPartner() && $this->business->payment_provider === PaymentProviderEnum::STRIPE_US && $this->business->country !== 'us' ? 'recipient' : 'full';

        $parameters = array_merge([
            'type' => 'custom',
            'country' => strtoupper($this->business->country),
            'default_currency' => strtolower($this->business->currency),
            'capabilities' => $this->generateDesiredCapabilities('recipient' === $service_agreement),
            'business_type' => $this->business->getStripeAccountBusinessType(),
            'metadata' => [
                'platform' => Facades\Config::get('app.name'),
                'version' => ConfigurationRepository::get('platform_version'),
                'environment' => Facades\Config::get('app.env'),
                'business_id' => $this->business->getKey(),
            ],
            'tos_acceptance' => [
                'service_agreement' => $service_agreement,
                'user_agent' => $this->clientUserAgent,
                'ip' => $this->clientIp,
                'date' => Facades\Date::now()->getTimestamp(),
            ]
        ], $this->generateData());

        $stripeAccount = Stripe\Account::create($parameters, [
            'stripe_version' => $this->stripeVersion,
        ]);

        // The custom account will not have publishable key and access token, therefore, some feature that calling
        // Stripe API with access token might fail.
        //
        $paymentProvider = new PaymentProvider;

        $paymentProvider->payment_provider = $this->business->payment_provider;
        $paymentProvider->payment_provider_account_type = 'custom';
        $paymentProvider->payment_provider_account_id = $stripeAccount->id;
        $paymentProvider->payment_provider_account_ready = false;
        $paymentProvider->onboarding_status = OnboardingStatus::PENDING_VERIFICATION;
        $paymentProvider->data = [ 'account' => $stripeAccount->toArray() ];

        // TODO - 20211117 - KEEP IN VIEW by Bankorh
        //   ----------------------------------------->>>
        //   I found there are two new columns as per following will be added later, from GrabPay and ShopeePay.
        //
        // $paymentProvider->onboarding_status = '';
        // $paymentProvider->reported = '';

        // We don't want to store dirty data in Stripe. Hence, as long as saving failed, we will just delete the Stripe
        // account and create again later when user retry.
        //
        try {
            $this->business->paymentProviders()->save($paymentProvider);
        } catch (Throwable $throwable) {
            $stripeAccount->delete();

            throw $throwable;
        }

        $this->businessPaymentProvider = $paymentProvider;
        $this->stripeAccount = $stripeAccount;

        if ($useStripeOnboarding) {
            return Facades\Response::redirectTo($this->generateCustomAccountLink('account_onboarding'));
        }

        return $stripeAccount;
    }
}
