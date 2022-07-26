<?php

namespace App\Jobs\Providers;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use App\Business\Charge;
use Carbon\Carbon;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;

class ConfirmYesterday implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $from;
    public $to;

    public function __construct($from = null, $to = null)
    {
        $this->from = $from;
        $this->to = $to;
    }

    /**
     * Execute the job.
     */
    public function handle() : void
    {
      Log::info('[AutoConfirm] Started');

      // If date is not defined - use yesterday
      if (!isset($this->from) || !isset($this->to)) {
        $dateFrom = Carbon::now()->subDays(1)->startOfDay();
        $dateTo = $dateFrom->copy()->endOfDay();

        $dateFrom = $dateFrom->toDateTimeString();
        $dateTo = $dateTo->toDateTimeString();
      } else {
        $dateTo = $this->to;
        $dateFrom = $this->from;
      }

      Log::info('[AutoConfirm] Dates to use: ' . $dateFrom . ' - ' . $dateTo);

      // Get charges
      $charges = Charge::where([
        'status' => ChargeStatus::SUCCEEDED
      ])->whereBetween('closed_at', [
        $dateFrom,
        $dateTo
      ])->whereIn('payment_provider', [
        PaymentProviderEnum::ZIP,
        PaymentProviderEnum::GRABPAY,
        PaymentProviderEnum::SHOPEE_PAY
      ])->get();

      Log::info('[AutoConfirm] Charges found: ' . $charges->count());

      foreach ($charges as $charge) {
        // Check if charge is not already confirmed
        $transaction = $charge->walletTransactions()->first();

        Log::info('[AutoConfirm] Charge: ' . $charge->id);

        if (!($transaction && $transaction->confirmed)) {
          try {
            Log::info('[AutoConfirm] Confirming charge: ' . $charge->id);
            $charge->business->confirmCharge($charge);
          } catch (\Exception $exception) {
            Log::error('Failed to confirm charge id ' . $charge->id . ': ' . $exception->getMessage());
          }
        }
      }
    }
}
