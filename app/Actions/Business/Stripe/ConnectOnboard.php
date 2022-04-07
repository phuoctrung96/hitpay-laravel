<?php

namespace App\Actions\Business\Stripe;

use App\Actions\Business\BasicDetails\Action;
use HitPay\Stripe\CustomAccount\Update;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ConnectOnboard extends Action
{
    /**
     * Get Connect Onboarding URL
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable|\Psr\SimpleCache\InvalidArgumentException
     */
    public function process()
    {
        $businessPaymentProvider = $this->business->paymentProviders()
            ->whereIn('payment_provider', ['stripe_sg', 'stripe_my'])
            ->where('payment_provider_account_type', 'custom')
            ->first();

        if (!$businessPaymentProvider) {
            throw new HttpException(400, 'This feature is unavailable for this business account ' . $this->business->id);
        }

        return Update::new($businessPaymentProvider->payment_provider)
            ->setBusiness($this->business)
            ->handle(true);
    }
}
