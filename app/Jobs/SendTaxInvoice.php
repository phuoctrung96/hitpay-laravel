<?php

namespace App\Jobs;

use App\Business;
use App\Enumerations\Business\ChargeStatus;
use App\Notifications\SendFile;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades;
use PDF;

class SendTaxInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Business $business;

    public Carbon $now;

    public Carbon $date;

    /**
     * Create a new job instance.
     *
     * @param  \App\Business  $business
     * @param  array  $data
     */
    public function __construct(Business $business, array $data)
    {
        $this->business = $business;

        $this->now = Facades\Date::today();
        $this->date = Carbon::create($data['year'], $data['month']);
    }

    /**
     * Execute the job.
     *
     * @throws \ReflectionException
     */
    public function handle()
    {
        $start = $this->date->clone()->startOfMonth();
        $end = $this->date->clone();

        if ($this->date->isSameMonth()) {
            $end->setDay($this->now->day)->endOfDay();
        } else {
            $end->endOfMonth();
        }

        $chargesQuery = $this->business->charges();

        $chargesQuery->whereIn($chargesQuery->qualifyColumn('status'), [
            ChargeStatus::SUCCEEDED,
            ChargeStatus::REFUNDED,
        ]);

        $chargesQuery->whereBetween('closed_at', [
            $start->toDateTimeString(),
            $end->toDateTimeString(),
        ]);

        $chargesQuery->select([
            $chargesQuery->qualifyColumn('id'),
            $chargesQuery->qualifyColumn('business_id'),
            $chargesQuery->qualifyColumn('home_currency_amount'),
            $chargesQuery->qualifyColumn('currency'),
            $chargesQuery->qualifyColumn('amount'),
            $chargesQuery->qualifyColumn('fixed_fee'),
            $chargesQuery->qualifyColumn('discount_fee'),
            $chargesQuery->qualifyColumn('home_currency_commission_amount'),
            $chargesQuery->qualifyColumn('closed_at'),
        ]);

        $charges = $chargesQuery->get();

        // For example, if your settlement currency is EUR and you process a 60 USD payment at a rate of 0.88 EUR per
        // 1 USD, the converted amount is 52.80 EUR (excluding the Stripe fee). If the rate is 0.86 EUR per 1 USD at
        // the time of refund, the amount deducted from your account balance is only 51.60 EUR.
        //
        $refundsQuery = $this->business->refunds();

        $refundsQuery->with([
            'charge' => function (BelongsTo $builder) {
                $builder->select([
                    'id',
                    'business_id',
                    'currency',
                    'home_currency_amount',
                    'fixed_fee',
                    'discount_fee',
                    'home_currency_commission_amount',
                    'exchange_rate',
                    'closed_at',
                ]);
            },
        ]);

        $refundsQuery->whereBetween($refundsQuery->qualifyColumn('created_at'), [
            $start->toDateTimeString(),
            $end->toDateTimeString(),
        ]);

        $refundsQuery->select([
            $refundsQuery->qualifyColumn('id'),
            $refundsQuery->qualifyColumn('business_charge_id'),
            $refundsQuery->qualifyColumn('status'),
            $refundsQuery->qualifyColumn('amount'),
            $refundsQuery->qualifyColumn('created_at'),
        ]);

        $refunds = $refundsQuery->get();

        // Total Sales $x
        // Less Total refunds $x
        // Net Sales $x

        // Total Fees ( fees paid for successful transactions + fees paid for refunds )

        $totalFee = $charges->sum('fixed_fee')
            + $charges->sum('discount_fee')
            + $charges->sum('home_currency_commission_amount');

        $charges = $charges->map(function (Business\Charge $charge) : Business\Charge {
            if (is_null($charge->home_currency_amount)) {
                if ($charge->currency === $this->business->currency) {
                    $charge->home_currency_amount = $charge->amount;
                } else {
                    throw new Exception(
                        'The `home_currency_amount` is empty while the currency of the charge and the business are not matched.'
                    );
                }
            }

            return $charge;
        });

        $totalSales = $charges->sum('home_currency_amount');

        $refundsGroupByChargeCurrencies = $refunds->groupBy(function (Business\Refund $refund) : string {
            return $refund->charge->currency;
        });

        $totalRefunds = 0;
        $refundsBreakdown = [];
        $hasOtherThanHomeCurrency = false;

        $refundsGroupByChargeCurrencies->each(
            function (
                Collection $collection, string $currency
            ) use (&$totalRefunds, &$refundsBreakdown, &$hasOtherThanHomeCurrency) : void {
                $refundOfThisCurrency = 0;
                $refundOfThisCurrencyInHomeCurrency = 0;

                if ($currency === $this->business->currency) {
                    $refundOfThisCurrency += $refundOfThisCurrencyInHomeCurrency += $collection->sum('amount');
                } else {
                    $hasOtherThanHomeCurrency = true;

                    $collection->each(function (
                        Business\Refund $refund
                    ) use (&$refundOfThisCurrency, &$refundOfThisCurrencyInHomeCurrency) : void {
                        $refundOfThisCurrency += $refund->amount;
                        $refundOfThisCurrencyInHomeCurrency += (int) bcmul(
                            $refund->amount,
                            $refund->charge->exchange_rate
                        );
                    });
                }

                $totalRefunds += $refundOfThisCurrencyInHomeCurrency;

                $refundsBreakdown[strtoupper($currency)] = getFormattedAmount($currency, $refundOfThisCurrency);
            }
        );

        $netSales = $totalSales - $totalRefunds;

        $data['date'] = $this->date;
        $data['business'] = $this->business;
        $data['total_sales'] = getFormattedAmount($this->business->currency, $totalSales);
        $data['total_refunds'] = getFormattedAmount($this->business->currency, $totalRefunds);
        $data['refunds_breakdown'] = $refundsBreakdown;
        $data['net_sales'] = getFormattedAmount($this->business->currency, $netSales);
        $data['total_fees'] = getFormattedAmount($this->business->currency, $totalFee);
        $data['is_approximately'] = $hasOtherThanHomeCurrency;

        $pdf = PDF::setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
        ])->loadView('hitpay-email.pdf.tax-invoice', $data);

        $this->business->notify(new SendFile('Fee Invoice for '.$this->date->format('F Y'), [
            'Please find attached your fee invoice',
        ], strtolower("fee-invoice-{$this->date->format('M-Y')}"), $pdf->output(), 'pdf'));
    }
}
