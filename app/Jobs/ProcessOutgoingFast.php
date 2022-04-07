<?php

namespace App\Jobs;

use App\Business\Commission;
use App\Business\Transfer;
use App\Exceptions\HitPayLogicException;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOutgoingFast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \App\Business\Transfer
     */
    public $transfer;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($transfer)
    {
        if (!$transfer instanceof Transfer && !$transfer instanceof Commission) {
            throw new Exception('The model is not acceptable for transfer');
        }

        $this->transfer = $transfer;
    }

    /**
     * Execute the job.
     *
     * @throws \Crypt_GPG_BadPassphraseException
     * @throws \Crypt_GPG_Exception
     * @throws \Crypt_GPG_FileException
     * @throws \Crypt_GPG_KeyNotFoundException
     * @throws \Crypt_GPG_NoDataException
     * @throws \PEAR_Exception
     * @throws \ReflectionException
     */
    public function handle()
    {
        try {
            $this->transfer->doFastTransfer();
        } catch (HitPayLogicException $exception) {
            Log::critical('Job Processing Fast Payment: '.$exception->getMessage());
        }
    }
}
