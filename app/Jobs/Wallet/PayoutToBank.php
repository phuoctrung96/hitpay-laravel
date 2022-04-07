<?php

namespace App\Jobs\Wallet;

use App\Business\Transfer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PayoutToBank implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \App\Business\Transfer
     */
    public $transfer;

    /**
     * Create a new job instance.
     */
    public function __construct(Transfer $transfer)
    {
        $this->transfer = $transfer;
    }

    /**
     * Execute the job.
     */
    public function handle() : void
    {
        try {
            $this->transfer->doFastTransfer();

            Log::info('A '.getFormattedAmount($this->transfer->currency, $this->transfer->amount)
                .' transfer was made to business '.$this->transfer->business->getName().'.');
        } catch (\Throwable $exception) {
            Log::error("Fast Payment Failed: {get_class($exception)} @ {$exception->getMessage()}");
        }
    }
}
