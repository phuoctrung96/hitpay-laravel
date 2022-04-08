<?php

namespace App\Jobs\Business\Stripe\Payout;

use App\Actions\Business\Stripe\Payouts;
use App\Business;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades;

class GenerateCsv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Business\Transfer $businessTransfer;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Business\Transfer $transfer)
    {
        $this->businessTransfer = $transfer;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        ( new Payouts\GenerateCsv )
            ->business($this->businessTransfer->business)
            ->businessTransfer($this->businessTransfer)
            ->process();

        try {
            Payouts\SendEmailForSuccessfulPayout::withBusiness($this->businessTransfer->business)
                ->businessTransfer($this->businessTransfer)
                ->process();
        } catch (Exception $exception) {
            Facades\Log::critical(
                "Failed to send email to business for the payout (ID : {$this->businessTransfer->getKey()}), got error: {$exception->getMessage()}. Trace:\n{$exception->getTraceAsString()}"
            );
        }
    }
}
