<?php

namespace App\Jobs;

use App\Business\PaymentProvider;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Models\BusinessPartner;
use App\Services\Rates\CustomRatesService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SetCustomPricingFromPartner implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private PaymentProvider $provider;
    private BusinessPartner $businessPartner;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(BusinessPartner $businessPartner, PaymentProvider $provider)
    {
        $this->provider = $provider;
        $this->businessPartner = $businessPartner;
    }

    /**
     * @param CustomRatesService $ratesService
     */
    public function handle(CustomRatesService $ratesService)
    {
        if($this->provider->payment_provider == PaymentProviderEnum::DBS_SINGAPORE) {
            $this->setPaynowCustomRates($ratesService);
        } elseif($this->provider->payment_provider == 'stripe_sg') {
            $this->setStripeCustomRates($ratesService);
        }
    }

    private function setPaynowCustomRates(CustomRatesService $customRatesService)
    {
        foreach ($this->businessPartner->getPricingItemsAttribute() as $pricing) {
            if(!empty($pricing['paynow_percentage'])) {
                $customRatesService->updateRates(
                    $this->provider,
                    $pricing['paynow_method'],
                    $pricing['paynow_channel'],
                    $pricing['paynow_percentage'],
                    $pricing['paynow_fixed_amount'],
                );
            }
        }

    }

    private function setStripeCustomRates(CustomRatesService $customRatesService)
    {
        foreach ($this->businessPartner->getPricingItemsAttribute() as $pricing) {
            if(!empty($pricing['stripe_percentage'])) {
                $customRatesService->updateRates(
                    $this->provider,
                    $pricing['stripe_method'],
                    $pricing['stripe_channel'],
                    $pricing['stripe_percentage'],
                    $pricing['stripe_fixed_amount'],
                );
            }
        }
    }
}
