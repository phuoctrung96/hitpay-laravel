<?php

namespace App\Console\Commands\OneTimeUse;

use App\Actions\Business\Settings\BankAccount\Store;
use App\Business;
use App\Business\PaymentProvider;
use ErrorException;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\ApiErrorException;

class SplitPayNowPaymentProviderBankAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'otu:split-paynow-payment-provider-bank-account {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Split the bank account in PayNow payment provider to independent bank account module';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        PaymentProvider::with([
            'business' => function (BelongsTo $business) {
                $business->with('verifiedData');
                $business->withCount('bankAccounts');
            },
        ])->where([
            'payment_provider' => 'dbs_sg',
        ])->each(function (PaymentProvider $paymentProvider) {
            if (!( $paymentProvider->business instanceof Business )) {
                $this->error("The payment provider '{$paymentProvider->getKey()}' doesn't attached to any business.");

                return;
            }

            if ($paymentProvider->business->business_type === 'partner') {
                if (!( $paymentProvider->business->verifiedData instanceof Business\Verification )) {
                    $this->error("The business '{$paymentProvider->business->getKey()}' (payment provider '{$paymentProvider->getKey()}') with type '{$paymentProvider->business->business_type}' doesn't have a valid verified data. The business will have to create the bank account manually.");

                    return;
                }
            }

            if ($paymentProvider->business->bank_accounts_count > 0) {
                $this->line("<comment>[ SKIPPED ]</comment> - The business '{$paymentProvider->business->getKey()}' (payment provider '{$paymentProvider->getKey()}') is already having bank account(s).");

                return;
            }

            try {
                [ $bankSwiftCode, $number ] = explode('@', $paymentProvider->payment_provider_account_id);
            } catch (ErrorException $exception) {
                $this->error("The business '{$paymentProvider->business->getKey()}' (payment provider '{$paymentProvider->getKey()}') got error '{$exception->getMessage()}' when getting bank swift code and account number hence the bank account has to be created manually.");

                return;
            }

            $holderTypesAvailable = [
                'business' => 'company',
                'personal' => 'individual',
            ];

            if (in_array($paymentProvider->business->business_type, $holderTypesAvailable)) {
                $holderType = $paymentProvider->business->business_type;
            } else {
                $holderType = $holderTypesAvailable[$paymentProvider->business->business_type] ?? null;
            }

            $data = [
                'bank_swift_code' => $bankSwiftCode,
                'branch_code' => null,
                'currency' => $paymentProvider->business->currency,
                'number' => $number,
                'number_confirmation' => $number,
                'holder_name' => $paymentProvider->data['account']['name'] ?? null,
                'holder_type' => $holderType,
                'use_in_hitpay' => true,
                'use_in_stripe' => false,
                'remark' => 'Extracted from payment provider PayNow',
            ];

            try {
                $handler = Store::withBusiness($paymentProvider->business)
                    ->data($data)
                    ->canIgnoreBranchCodeForCertainCountries();

                if ($this->option('dry-run')) {
                    $handler->dryRun();
                }

                $handler->process();

                $this->line("<info>[    √    ]</info> - The business ({$paymentProvider->business->getKey()}) has the bank account ready.");
            } catch (ValidationException $exception) {
                $this->error("The business '{$paymentProvider->business->getKey()}' (payment provider '{$paymentProvider->getKey()}') got validation errors when creating bank account and will have to create manually.");

                $counter = 0;

                Collection::make($exception->errors())->each(function (array $messages) use (&$counter) {
                    foreach ($messages as $message) {
                        $counterText = str_pad(++$counter, 2, '0', STR_PAD_LEFT);

                        $this->line("            <fg=red;>-</> [ {$counterText} ] <fg=red;>-</> {$message}");
                    }
                });
            } catch (ApiErrorException $exception) {
                $this->error("The business '{$paymentProvider->business->getKey()}' (payment provider '{$paymentProvider->getKey()}') is having issue when syncing bank account to Stripe. {$exception->getMessage()} The business will have to sync the bank account manually via update.");

                return;
            }
        });

        return 0;
    }

    /**
     * @inheritdoc
     */
    public function error($message, $verbosity = null) : void
    {
        $this->line("<error>[  ERROR  ]</error> - <fg=red;>{$message}</>", null, $verbosity);
    }
}
