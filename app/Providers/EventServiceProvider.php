<?php

namespace App\Providers;

use App\Business\BusinessReferral;
use App\Events\Business\Created;
use App\Events\Business\RecurrentChargeSucceeded;
use App\Events\Business\SentInvoice;
use App\Events\Business\SuccessCharge;
use App\Listeners\ChargePostProcessor;
use App\Listeners\CheckCompliance;
use App\Listeners\SendReceipt;
use App\Listeners\SendRecurrentReceipt;
use App\Listeners\SendWelcomeEmail;
use App\Listeners\SetInvoiceAsPaid;
use App\Listeners\PaymentRequestCompleted;
use App\Listeners\PaymentRequestVendorCallback;
use App\Observers\BusinessReferralObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Shopify\ShopifyExtendSocialite;
use SocialiteProviders\Xero\XeroExtendSocialite;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            // SendEmailVerificationNotification::class,
        ],
        SocialiteWasCalled::class => [
            ShopifyExtendSocialite::class,
            XeroExtendSocialite::class,
        ],
        Created::class => [
            SendWelcomeEmail::class,
        ],
        SentInvoice::class => [
            \App\Listeners\SentInvoice::class
        ],
        SuccessCharge::class => [
            SetInvoiceAsPaid::class,
            SendReceipt::class,
            PaymentRequestCompleted::class,
            PaymentRequestVendorCallback::class,
            ChargePostProcessor::class
        ],
        RecurrentChargeSucceeded::class => [
            SendRecurrentReceipt::class
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        BusinessReferral::observe(BusinessReferralObserver::class);
    }
}
