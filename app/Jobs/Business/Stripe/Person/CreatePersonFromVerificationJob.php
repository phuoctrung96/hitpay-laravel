<?php

namespace App\Jobs\Business\Stripe\Person;

use App\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class CreatePersonFromVerificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $requestParams;

    public Business\Verification $verification;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Business\Verification $verification, array $requestParams)
    {
        $this->verification = $verification;

        $this->requestParams = $requestParams;
    }

    /**
     * @return void
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Throwable
     */
    public function handle()
    {
        $business = $this->verification->business()->first();

        if (!App::environment('production')) {
            Log::info("Business ID {$business->getKey()}");
        }

        try {
            \App\Actions\Business\Settings\Verification\CreatePersonFromVerification::withBusiness($business)
                ->data($this->requestParams)
                ->setVerification($this->verification)
                ->setPaymentProvider()
                ->process();
        } catch (\Exception $exception) {
            Log::critical("Trying create person from CreatePersonFromVerificationJob for the
                business (ID : {$business->getKey()}) with error " . $exception->getMessage());
        }
    }
}
