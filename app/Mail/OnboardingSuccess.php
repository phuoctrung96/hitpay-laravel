<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;

class OnboardingSuccess extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($provider)
    {
        $this->provider = $provider;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $payment_provider = PaymentProviderEnum::displayName($this->provider->payment_provider);
        $integration_link = route('dashboard.business.gateway.index', [$this->provider->business->id]);

        return $this
          ->subject('GrabPay onboarding completed - HitPay')
          ->view('hitpay-email.onboarding-success', compact('payment_provider', 'integration_link'));
    }
}
