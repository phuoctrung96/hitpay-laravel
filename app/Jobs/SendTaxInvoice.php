<?php

namespace App\Jobs;

use App\Business;
use App\Enumerations\Business\ChargeStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Notifications\SendFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use PDF;
use Illuminate\Support\Facades\DB;

class SendTaxInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $business;

    public $month;
    public $year;

    /**
     * Create a new job instance.
     *
     * @param \App\Business $business
     * @param $month
     * @param $year
     */
    public function __construct(Business $business, $data)
    {
        $this->business = $business;
        $this->month = $data['month'];
        $this->year = $data['year'];
    }

    /**
     * Execute the job.
     *
     * @throws \ReflectionException
     */
    public function handle()
    {
        if ($this->month == now()->month)
            $monthDate = Carbon::createFromDate($this->year, $this->month, now()->day);
        else
            $monthDate = Carbon::createFromDate($this->year, $this->month, 1)->endOfMonth();

        $charges = DB::table('business_charges')
            ->selectRaw('sum(home_currency_amount/100) AS total_volume, sum((fixed_fee + discount_fee + home_currency_commission_amount)/100) AS fees')
            ->whereIn('status', [
                ChargeStatus::SUCCEEDED,
                ChargeStatus::REFUNDED,
            ])
            ->where('business_id', $this->business->id)
            ->whereMonth('closed_at', '=', $this->month)->whereYear('created_at', '=', $this->year)
            ->get()
            ->groupBy('business_id')
            ->first()
            ->first();

        $vars['monthDate'] = $monthDate;
        $vars['business'] = $this->business;
        $vars['fee'] = $charges->fees;
        $vars['total_volume'] = $charges->total_volume;

        $pdf = PDF::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true])->loadView('hitpay-email.pdf.tax-invoice', $vars);

        $this->business->notify(new SendFile('Fee Invoice for ' .$monthDate->format('F Y'), [
            'Please find attached your fee invoice',
        ], 'fee-invoice-'.$monthDate->format('M-Y'), $pdf->output(), 'pdf'));
    }
}
