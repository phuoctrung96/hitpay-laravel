<?php

namespace App\Jobs\Providers\GrabPay;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use League\Csv\Writer;
use App\Business\Charge;
use App\Business\PaymentIntent;
use App\Enumerations\Business\ChargeStatus;
use Exception;

class SaveCompletedCharge
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $content;

    /**
     * Create a new job instance.
     */
    public function __construct(string $content)
    {
        $this->content = $content;
    }

    /**
     * Execute the job.
     */
    public function handle() : void
    {
      try {
        $path = 'grabpay/charge-completed';

        if (Storage::disk('s3')->exists($path)) {
          $filename = $path . '/' . gmdate("dmYHis") . '.txt';
  
          Storage::disk('s3')->put($filename, $this->content);
        }  
      } catch (Exception $exception) {
      }
    }
}
