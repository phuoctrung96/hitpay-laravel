<?php

namespace App\Jobs\Business\Stripe\Person;

use App\Business;
use HitPay\Stripe\CustomAccount\Person;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class Push implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Business $business;

    public array $person;

    /**
     * Create a new job instance.
     *
     * @param  string  $paymentProvider
     * @param  array  $person
     */
    public function __construct(Business $business, array $person)
    {
        $this->business = $business;
        $this->person = $person;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() : void
    {
        Person\Push::new($this->business->payment_provider)->setBusiness($this->business)->handle($this->person);
    }
}
