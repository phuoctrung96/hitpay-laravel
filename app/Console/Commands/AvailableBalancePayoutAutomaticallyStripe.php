<?php

namespace App\Console\Commands;

use App\Business;
use App\Enumerations\Business\Wallet\Type;
use App\Enumerations\PaymentProvider;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Throwable;

class AvailableBalancePayoutAutomaticallyStripe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hitpay:available-balance-payout-automatically-stripe {time}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Payout available balance automatically for Stripe';

    /**
     * Execute the console command.
     */
    public function handle() : int
    {
        $time = $this->argument('time');

        if (!in_array($time, [ '00:00:00', '09:30:00' ])) {
            throw new Exception('Invalid time');
        }

        $query = Business::query();

        $query->with([
            'paymentProviders' => function (HasMany $query) {
                $query->whereIn('payment_provider', [PaymentProvider::STRIPE_MALAYSIA, PaymentProvider::STRIPE_US]);
            },
        ]);

        $query->join('business_wallets', $query->qualifyColumn('id'), 'business_wallets.business_id');

        $query->where($query->qualifyColumn('auto_pay_to_bank'), true);

        if ($time === '00:00:00') {
            $query->where($query->qualifyColumn('auto_pay_to_bank_day'), 'daily');
        } else {
            $today = Date::now();

            $includes = [
                'daily',
                'weekly_'.strtolower($today->shortDayName),
            ];

            if ($today->day === 1) {
                $includes[] = 'monthly_1';
            }

            $query->whereIn($query->qualifyColumn('auto_pay_to_bank_day'), $includes);
        }

        $query->where($query->qualifyColumn('auto_pay_to_bank_time'), $time);
        $query->whereColumn('business_wallets.currency', 'businesses.currency');
        $query->where('business_wallets.type', Type::AVAILABLE);
        $query->where('business_wallets.balance', '>=', 100); // Set the minimum payout to be $1.

        $query->select($query->qualifyColumn('*'))->each(function (Business $business) {
            if ($business->paymentProviders->count() > 1) {
                Log::critical('Business # '.$business->getKey().' is detected with two "stripe" accounts.');
            }

            $paymentProvider = $business->paymentProviders->first();

            if (!($paymentProvider instanceof Business\PaymentProvider)) {
                Log::channel('available-balance-payouts')->info("Payout was not made to business ID {$business->getKey()} ({$business->getName()}) because the payment provider hasn't set.");

                return 1;
            }

            try {
                $transfer = $business->payoutToStripe($paymentProvider, $business->currency);
                $amountText = getFormattedAmount($transfer->currency, $transfer->amount);

                Log::channel('available-balance-payouts')->info("A {$amountText} payout was created for business ID {$business->getKey()} ({$business->getName()}).");

                try {
                    $transfer->doStripeConnectTransfer();

                    Log::channel('available-balance-payouts')->info("A {$amountText} transfer was made to business ID {$business->getKey()} ({$business->getName()}).");

                    sleep(1);
                } catch (Throwable $exception) {
                    Log::channel('available-balance-payouts')->error("Stripe Wallet Transfer Failed: {$exception->getMessage()}");
                }
            } catch (Throwable $exception) {
                Log::channel('available-balance-payouts')->error("Payout Creation Failed: {$exception->getMessage()}");
            }


        });

        return 0;
    }
}

