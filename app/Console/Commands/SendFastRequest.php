<?php

namespace App\Console\Commands;

use App\Business;
use App\Business\Charge;
use App\Business\Transfer;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\SupportedCurrencyCode;
use App\Enumerations\PaymentProvider;
use App\Models\Business\SpecialPrivilege;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendFastRequest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hitpay:dbs-fast-payment
                {--date=}
                {--check=false}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Do daily fast payment.';

    /**
     * Execute the console command.
     *
     * @throws \Exception
     */
    public function handle() : int
    {
        $date = $this->option('date');

        if ($date !== null) {
            $date = Date::createFromFormat('Y-m-d', $date);
        } else {
            $date = Date::yesterday();
        }

        $this->info('Closed Date   : '.$date->toDateString());

        $theNextDay = $date->clone()->addDay();

        $this->info('Transfer Date : '.$theNextDay->toDateString());

        $businessIds = Charge::where('payment_provider', PaymentProvider::DBS_SINGAPORE)
            ->where('payment_provider_transfer_type', 'manual')
            ->whereIn('payment_provider_charge_type', ['inward_credit_notification', 'collection'])
            ->whereIn('status', [
                ChargeStatus::SUCCEEDED,
                ChargeStatus::REFUNDED,
            ])
            ->whereDate('closed_at', $date)
            ->whereDoesntHave('fastTransfers')
            ->groupBy('business_id')
            ->pluck('business_id');

        foreach ($businessIds as $businessId) {
            $business = Business::find($businessId);

            $transferMade = $business->transfers()
                ->where('payment_provider', PaymentProvider::DBS_SINGAPORE)
                ->where('payment_provider_transfer_type', 'fast')
                ->where('payment_provider_transfer_method', 'destination')
                ->whereDate('created_at', $theNextDay)->first();

            if ($transferMade) {
                Log::critical('The business [ID:'.$businessId.'] already has a transfer for date '.$date->toDateString()
                    .' on '.$theNextDay->toDateString().'. Please if this command was re-ran, ignore this message. This'
                    .' is a check to ensure there\'s no duplicate transfer.');

                continue;
            }

            $charges = $business->charges()
                ->where('payment_provider', PaymentProvider::DBS_SINGAPORE)
                ->where('payment_provider_transfer_type', 'manual')
                ->where('payment_provider_charge_type', 'inward_credit_notification')
                ->whereIn('status', [
                    ChargeStatus::SUCCEEDED,
                    ChargeStatus::REFUNDED,
                ])
                ->whereDoesntHave('fastTransfers')
                ->whereDate('closed_at', $date)->get();

            if ($charges->where('currency', '!=', SupportedCurrencyCode::SGD)->count()) {
                Log::critical('The business [ID:'.$businessId.'] has non-SGD PayNow payment, please check. This was'
                    .' detected when making fast payment to the business. The transfer for date '
                    .$theNextDay->toDateString().' was not made.');

                continue;
            }

            $transferableAmount = 0;

            foreach ($charges as $charge) {
                $transferableAmount = $transferableAmount + ($charge->home_currency_amount - $charge->getTotalFee());
            }

            $bankAccount = $business->bankAccounts()->where([
                'country' => $business->country,
                'currency' => $business->currency,
                'hitpay_default' => true,
            ])->first();

            if ($bankAccount) {
                $bankAccountId = "{$bankAccount->bank_swift_code}@{$bankAccount->number}";
            } else {
                $bankAccountId = null;
            }

            if ($this->option('check') === 'true') {
                $this->info('');
                $this->info('ID            : '.$business->id);
                $this->info('Name          : '.$business->getName());
                $this->info('Amount        : '.getFormattedAmount($business->currency, $transferableAmount));
                $this->info('Charges Count : '.$charges->count());
                $this->info('Bank Account  : '.$bankAccountId);

                continue;
            }

            if ($transferableAmount <= 0) {
                continue;
            }

            sleep(1);
            $transferModel = new Transfer;
            $transferModel->business_id = $businessId;
            $transferModel->payment_provider_transfer_method = 'destination';
            $transferModel->payment_provider = PaymentProvider::DBS_SINGAPORE;
            $transferModel->currency = SupportedCurrencyCode::SGD;
            $transferModel->amount = $transferableAmount;
            $transferModel->remark = 'HitPay payouts for '.$date->toDateString();
            $transferModel->payment_provider_account_id = $bankAccountId;

            if ($bankAccount) {
                $transferData['bank_account'] = $bankAccount->only([
                    'id',
                    'country',
                    'currency',
                    'bank_swift_code',
                    'bank_routing_number',
                    'number',
                    'holder_name',
                    'holder_type',
                    'stripe_platform',
                    'stripe_external_account_id',
                ]);
            } else {
                Log::alert("The business (ID : {$businessId}) is having a HitPay payout but have no bank account setup.");
            }

            $paymentProvider = $business->paymentProviders()
                ->where('payment_provider', PaymentProvider::DBS_SINGAPORE)
                ->first();

            if ($paymentProvider) {
                $transferData['payment_provider'] = $paymentProvider->data;
            } else {
                Log::alert("The business (ID : {$businessId}) is having a HitPay payout but have no PayNow payment provider setup.");
            }

            $transferModel->data = $transferData;
            $transferModel->status = 'request_pending';

            try {

                DB::beginTransaction();

                $transferModel->save();
                $transferModel->charges()->attach($charges->pluck('id'));

                DB::commit();

                $transferPaused = $business
                    ->specialPrivileges()
                    ->where('special_privilege', SpecialPrivilege::TRANSFER_PAUSED)
                    ->exists();

                if (!$transferPaused) {
                    $transferModel->doFastTransfer();
                }

                Log::info('A '.getFormattedAmount($transferModel->currency, $transferModel->amount).' transfer was made'
                    .' to business '.$business->getName().'.');
            } catch (Throwable $exception) {
                Log::error('Fast Payment Failed: '.$exception->getMessage());
            }
        }

        return 0;
    }
}
