<?php

namespace App\Console\Commands;

use App\Business;
use App\Business\Charge;
use App\Business\Commission;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\SupportedCurrencyCode;
use App\Enumerations\PaymentProvider;
use App\Models\Business\SpecialPrivilege;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessCommissionPayout extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hitpay:commission-payout
                {--date=}
                {--check=false}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process commission payout just like fast payout.';

    /**
     * Execute the console command.
     *
     * @return mixed
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

        $this->info('Commission Date : '.$theNextDay->toDateString());

        $businessIds = Charge::where('status', ChargeStatus::SUCCEEDED)
            ->whereDate('closed_at', $date)
            ->whereDoesntHave('commissions')
            ->whereNotNull('platform_business_id')
            ->groupBy('platform_business_id')
            ->pluck('platform_business_id');

        foreach ($businessIds as $businessId) {
            $business = Business::find($businessId);

            $commissionPaid = $business->commissions()->whereDate('created_at', $theNextDay)->first();

            if ($commissionPaid) {
                Log::critical('The business [ID:'.$businessId.'] already has a commission payout for date '
                    .$date->toDateString().' on '.$theNextDay->toDateString().'. Please if this command was re-ran, ' .
                    'ignore this message. This is a check to ensure there\'s no duplicate commission payout.');

                continue;
            }

            // platform charge
            $charges = $business->platformCharges()
                ->where('status', ChargeStatus::SUCCEEDED)
                ->whereDate('closed_at', $date)
                ->whereDoesntHave('commissions')
                ->get();

            if ($charges->where('currency', '!=', SupportedCurrencyCode::SGD)->count()) {
                Log::critical('The business [ID:'.$businessId.'] has non-SGD PayNow payment, please check. This was'
                    .' detected when making fast payment to the business. The commission for date '
                    .$theNextDay->toDateString().' was not made.');

                continue;
            }

            $commissionAmount = 0;

            foreach ($charges as $charge) {
                /** @var Charge $charge */
                $commissionAmount = $commissionAmount + $charge->home_currency_commission_amount;
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
                $this->info('Amount        : '.getFormattedAmount($business->currency, $commissionAmount));
                $this->info('Charges Count : '.$charges->count());
                $this->info('Bank Account  : '.$bankAccountId);

                continue;
            }

            if ($commissionAmount <= 0) {
                continue;
            }

            $commissionModel = new Commission;
            $commissionModel->business_id = $businessId;
            $commissionModel->payment_provider_transfer_method = 'destination';
            $commissionModel->payment_provider = PaymentProvider::DBS_SINGAPORE;
            $commissionModel->currency = SupportedCurrencyCode::SGD;
            $commissionModel->amount = $commissionAmount;
            $commissionModel->remark = 'HitPay\'s commission payouts for '.$date->toDateString();
            $commissionModel->payment_provider_account_id = $bankAccountId;

            if ($bankAccount) {
                $commissionData['bank_account'] = $bankAccount->only([
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
                $commissionData['payment_provider'] = $paymentProvider->data;
            } else {
                Log::alert("The business (ID : {$businessId}) is having a HitPay payout but have no PayNow payment provider setup.");
            }

            $commissionModel->data = $commissionData;
            $commissionModel->status = 'request_pending';

            try {

                DB::beginTransaction();

                $commissionModel->save();
                $commissionModel->charges()->attach($charges->pluck('id'));

                DB::commit();

                $transferPaused = $business
                    ->specialPrivileges()
                    ->where('special_privilege', SpecialPrivilege::TRANSFER_PAUSED)
                    ->exists();

                if (!$transferPaused) {
                    $commissionModel->doFastTransfer();
                }

                Log::info('A '.getFormattedAmount($commissionModel->currency, $commissionModel->amount).' commission was made'
                    .' to business '.$business->getName().'.');
            } catch (Throwable $exception) {
                Log::error('Fast Payment Failed: '.$exception->getMessage());
            }
        }

        return 0;
    }
}
