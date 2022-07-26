<?php

namespace App\Models\Business;

use App\Business\PaymentProvider;
use App\Business\PaymentProviderRate;
use App\Enumerations;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

trait HasPaymentProviders
{
    /**
     * Get the active payment providers only.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activePaymentProviders() : HasMany
    {
        return $this->paymentProviders()->whereIn('payment_provider', [
            Enumerations\PaymentProvider::DBS_SINGAPORE,
            Enumerations\PaymentProvider::GRABPAY,
            Enumerations\PaymentProvider::HOOLAH,
            Enumerations\PaymentProvider::SHOPEE_PAY,
            Enumerations\PaymentProvider::STRIPE_MALAYSIA,
            Enumerations\PaymentProvider::STRIPE_SINGAPORE,
            Enumerations\PaymentProvider::STRIPE_US,
            Enumerations\PaymentProvider::ZIP,
        ]);
    }

    /**
     * Get the Stripe account of the business.
     *
     * @return \App\Business\PaymentProvider|null
     */
    public function stripeAccount() : ?PaymentProvider
    {
        return $this->activePaymentProviders->where('payment_provider', $this->payment_provider)->first();
    }

    /**
     * Indicate if the business is using custom account for Stripe.
     *
     * @return bool
     */
    public function usingStripeCustomAccount() : bool
    {
        $stripeAccount = $this->stripeAccount();

        return $stripeAccount && $stripeAccount->payment_provider_account_type === 'custom';
    }

    /**
     * Determine if the business should have a Stripe Custom Account.
     *
     * @return bool
     */
    public function shouldHaveStripeCustomAccount() : bool
    {
        if ($this->isPartner()) {
            if ($this->country === Enumerations\CountryCode::SINGAPORE) {
                return false;
            }
        }

        return true;
    }

    /**
     * Indicate if the business is using standard account for Stripe.
     *
     * @return bool
     */
    public function usingStripeStandardAccount() : bool
    {
        $stripeAccount = $this->stripeAccount();

        return $stripeAccount && $stripeAccount->payment_provider_account_type === 'standard';
    }

    /**
     * If the business is using custom account for Stripe, check if the account is ready (verified).
     *
     * @return bool
     */
    public function isStripeCustomAccountReady() : bool
    {
        $stripeAccount = $this->stripeAccount();

        if ($stripeAccount && $stripeAccount->payment_provider_account_type === 'custom') {
            return $stripeAccount->payment_provider_account_ready;
        }

        return true;
    }

    public function allowProvider($provider)
    {
        switch (config('services.'.$provider.'.enabled', 'none')) {
            case 'none':
                return false;

            case 'all_users':
                return true;

            case 'whitelist_only':
                $whitelist = explode(',', config('services.'.$provider.'.whitelist'));

                return in_array($this->id, $whitelist);

            default:
                return false;
        }
    }

    public function allowGrabPay()
    {
        return $this->allowProvider('grabpay');
    }

    public function allowZip()
    {
        return $this->allowProvider('zip');
    }

    public function allowShopee()
    {
        return $this->allowProvider('shopee');
    }

    /**
     * Get the payment providers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Business\PaymentProvider|\App\Business\PaymentProvider[]
     */
    public function paymentProviders() : HasMany
    {
        return $this->hasMany(PaymentProvider::class, 'business_id', 'id');
    }

    public function rates() : HasManyThrough
    {
        return $this->hasManyThrough(PaymentProviderRate::class, PaymentProvider::class,
            'business_id', 'business_payment_provider_id', 'id', 'id');
    }

    public function getStripeAccountBusinessType() : string
    {
        $typeMap = [
            'business' => 'company',
            'personal' => 'individual',
            'partner' => 'company',
        ];

        return $typeMap[$this->business_type] ?? $this->business_type;
    }
}
