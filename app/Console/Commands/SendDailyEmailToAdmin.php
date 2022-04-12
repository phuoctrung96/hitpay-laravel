<?php

namespace App\Console\Commands;

use App\Business;
use App\Business\Charge;
use App\Business\Commission;
use App\Business\PaymentProvider;
use App\Business\Refund;
use App\Business\Transfer;
use App\Enumerations\Business\ChargeStatus;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;
use SplTempFileObject;
use Throwable;

class SendDailyEmailToAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:daily-email { --email= }{ --request= }{ --start_date= }{ --end_date= }{ --period= }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily email to admin. Everything about yesterday.';

    /**
     * Execute the console command.
     *
     * @throws \League\Csv\CannotInsertRecord
     * @throws \League\Csv\Exception
     * @throws \ReflectionException
     */
    public function handle()
    {
        $startTime = Date::now();

        $request = $this->option('request');

        $counter = [
            'businesses' => 0,
            'charges' => 0,
            'payouts' => 0,
            'commissions' => 0,
            'refunds' => 0,
        ];

        if (empty($request)) {
            $request = 'all';
        }

        try {
            Log::info(__CLASS__.' start exporting '.$request.' for '.$this->option('period'));
        } catch (Throwable $throwable) {
        }

        if (( $period = $this->option('period') )) {
            if ($period === 'last_week') {
                $startDate = Date::now()->startOfWeek()->subWeek()->startOfDay();
                $endDate = $startDate->clone()->addDays(6)->endOfDay();
            } else {
                $startDate = Date::yesterday()->startOfDay();
                $endDate = $startDate->clone()->endOfDay();
            }
        } else {
            if ($startDate = $this->option('start_date')) {
                $startDate = Date::createFromFormat('Y-m-d', $startDate)->startOfDay();
            }

            if ($endDate = $this->option('end_date')) {
                $endDate = Date::createFromFormat('Y-m-d', $endDate)->endOfDay();
            }
        }

        if ($request === 'all' || $request === 'businesses') {
            $businessCsv = Writer::createFromFileObject(new SplTempFileObject());
            $businessCsv->insertOne([
                '#',
                'ID',
                'Name',
                'Display Name',
                'Category',
                'Country',
                'Website',
                'Referred Channel',
                'Currency',
                'Client Key + Secret',
                'Integrations',
                'PayNow Enabled',
                'Stripe Enabled',
                'Stripe Account ID',
                'Business Email',
                'HitPay Login Email',
                'Stripe Support Email',
                'Stripe User Email (Inaccurate)',
                'Business Phone Number',
                'Stripe Support Phone Number',
                'Business Created Date',
                'MyInfo Verified Date',
                'Business Deleted',
            ]);

            $businessData = [];

            Business::with('owner', 'paymentProviders', 'client', 'gatewayProviders', 'verifiedData', 'merchantCategory')
                ->orderBy('id')
                ->each(function (Business $business) use (&$counter, &$businessData) {
                    $paynowPaymentProvider = $business->paymentProviders
                        ->where('payment_provider', $business->payment_provider)
                        ->first();
                    $stripePaymentProvider = $business->paymentProviders
                        ->where('payment_provider', $business->payment_provider)
                        ->first();

                    $integrations = [];

                    if ($business->gatewayProviders->count()) {
                        $business->gatewayProviders->each(function (Business\GatewayProvider $gatewayProvider) use (
                            &$integrations
                        ) {
                            if (is_array($gatewayProvider->array_methods)) {
                                $methods = implode(', ', $gatewayProvider->array_methods);
                            } elseif (is_string($gatewayProvider->array_methods)) {
                                $methods = $gatewayProvider->array_methods;
                            } else {
                                $methods = 'Error, contact developer. Detected data type: '
                                    .gettype($gatewayProvider->array_methods);
                            }

                            $integrations[] = 'Name: '.$gatewayProvider->name.', Methods: '.$methods;
                        });
                    }

                    if ($business->verifiedData instanceof Business\Verification) {
                        $verifiedDate = $business->verifiedData->verified_at;
                    }

                    $businessData[$business->id] = [
                        ++$counter['businesses'],
                        $business->id,
                        $business->name,
                        $business->display_name,
                        '', // $business->categories()->first() ? $business->categories()->first()->category : '',
                        $business->country,
                        $business->website,
                        $business->referred_channel,
                        $business->currency,
                        $business->client->map(function (\App\Client $client) {
                            return $client->getKey(). ' @ '.$client->secret;
                        })->implode("\n"),
                        implode(" / ", $integrations),
                        $paynowPaymentProvider instanceof PaymentProvider ? 'Yes' : 'No',
                        $stripePaymentProvider instanceof PaymentProvider ? 'Yes' : 'No',
                        $stripePaymentProvider->payment_provider_account_id ?? null,
                        $business->email,
                        $business->owner->email ?? null,
                        $stripePaymentProvider->data['support_email'] ?? null,
                        $stripePaymentProvider->data['email'] ?? null,
                        $business->phone_number,
                        $stripePaymentProvider->data['support_phone'] ?? null,
                        $business->created_at->toDateTimeString(),
                        $verifiedDate ?? null,
                        $business->deleted_at,
                    ];
                });

            $businessCsv->insertAll($businessData);

            unset($businessData);
        }

        if ($request === 'all' || $request === 'charges') {
            $chargesCsv = Writer::createFromFileObject(new SplTempFileObject());
            $chargesCsv->insertOne([
                '#',
                'ID',
                'Business ID',
                'Business Name',
                'Platform',
                'Platform Reference ID',
                'Transfer Type',
                'Email Recipients',
                'Currency',
                'Amount',
                'Application Fee Currency',
                'Application Fee Amount',
                'HitPay Fee Currency',
                'HitPay Fee Amount',
                'Channel',
                'Plugin',
                'Plugin Reference',
                'Plugin Callback',
                'Method',
                'Details',
                'Status',
                'Relatable Type',
                'Relatable ID',
                'Remark',
                'Created Date',
            ]);

            $chargeData = [];

            $charges = Charge::with('receiptRecipients')->with([
                'business' => function (Relation $query) {
                    $query->withTrashed();
                }
            ])->whereNotNull('closed_at');

            if ($startDate) {
                $charges->whereDate('closed_at', '>=', $startDate->toDateTimeString());
            }

            if ($endDate) {
                $charges->whereDate('closed_at', '<=', $endDate->toDateTimeString());
            }

            $charges->where('status', ChargeStatus::SUCCEEDED)
                ->each(function (Charge $charge) use (&$counter, &$chargeData) {
                    $emails = $charge->receiptRecipients->pluck('email');

                    $emails->push($charge->customer_email);

                    $thisCharge = [
                        ++$counter['charges'],
                        $charge->id,
                        $charge->business_id,
                        $charge->business ? $charge->business->name : '',
                        $charge->payment_provider,
                        $charge->payment_provider_charge_id,
                        $charge->payment_provider_transfer_type,
                        $emails->implode(' / '),
                        $charge->currency,
                        getReadableAmountByCurrency($charge->currency, $charge->amount),
                    ];

                    if ($charge->payment_provider_transfer_type === 'direct' && $charge->home_currency) {
                        $directFee = getReadableAmountByCurrency($charge->home_currency, $charge->getTotalFee());
                    } elseif (($charge->payment_provider_transfer_type === 'wallet'
                            || $charge->payment_provider_transfer_type === 'destination'
                            || $charge->payment_provider_transfer_type === 'manual') && $charge->home_currency) {
                        $destinationFee = getReadableAmountByCurrency($charge->home_currency, $charge->getTotalFee());
                    }

                    $thisCharge[] = isset($directFee) ? $charge->home_currency : null;
                    $thisCharge[] = $directFee ?? null;
                    $thisCharge[] = isset($destinationFee) ? $charge->home_currency : null;
                    $thisCharge[] = $destinationFee ?? null;
                    $thisCharge[] = $charge->channel;
                    $thisCharge[] = $charge->plugin_provider;
                    $thisCharge[] = $charge->plugin_provider_reference;
                    $thisCharge[] = $charge->is_successful_plugin_callback ? 'True' : 'False';
                    $thisCharge[] = $charge->payment_provider_charge_method;
                    $thisCharge[] = $charge->getChargeDetails();
                    $thisCharge[] = $charge->status;
                    $thisCharge[] = $charge->business_target_type;
                    $thisCharge[] = $charge->business_target_id;
                    $thisCharge[] = $charge->remark;
                    $thisCharge[] = $charge->closed_at->toDateTimeString();

                    $chargeData[] = $thisCharge;

                    unset($directFee, $destinationFee);
                });

            $chargesCsv->insertAll($chargeData);

            unset($chargeData);
        }

        // TODO - For this case, when `$request` is all, only refunds will be exported. We should split this later.
        //   Also spit into different commands.
        //
        if ($request === 'all' || $request === 'refunds' || $request === 'cashbacks' || $request === 'campaigns') {
            $refundsCsv = Writer::createFromFileObject(new SplTempFileObject());
            $refundsCsv->insertOne([
                '#',
                'ID',
                'DBS ID',
                'Charge ID',
                'Business ID',
                'Business Name',
                'Platform',
                'Platform Reference Type',
                'Platform Reference ID',
                'Charge Currency',
                'Charge Amount',
                'Refund Amount',
                'Created Date',
            ]);

            $refundsData = [];

            $refunds = Refund::with([
                'charge' => function (BelongsTo $query) {
                    $query->with([
                        'business' => function (Relation $query) {
                            $query->withTrashed();
                        },
                    ]);
                },
            ]);

            if ($startDate) {
                $refunds->whereDate('created_at', '>=', $startDate->toDateTimeString());
            }

            if ($endDate) {
                $refunds->whereDate('created_at', '<=', $endDate->toDateTimeString());
            }

            if ($request == 'cashbacks'){
                $refunds->where('is_cashback', 1);
            } else {
                $refunds->where('is_cashback', 0);
            }

            if ($request == 'campaigns'){
                $refunds->where('is_campaign_cashback', 1);
            } else {
                $refunds->where('is_campaign_cashback', 0);
            }

            $refunds->each(function (Refund $refund) use (&$counter, &$refundsData) {
                $refundsData[] = [
                    ++$counter['refunds'],
                    $refund->id,
                    str_replace('-', '', $refund->id),
                    $refund->business_charge_id,
                    $refund->charge->business_id,
                    $refund->charge->business->name,
                    $refund->payment_provider,
                    $refund->payment_provider_refund_type,
                    $refund->payment_provider_refund_id,
                    $refund->charge->currency,
                    getReadableAmountByCurrency($refund->charge->currency, $refund->charge->amount),
                    getReadableAmountByCurrency($refund->charge->currency, $refund->amount),
                    $refund->created_at->toDateTimeLocalString(),
                ];
            });

            $refundsCsv->insertAll($refundsData);

            unset($refundsData);
        }

        if ($request === 'all' || $request === 'commissions') {
            $commissionsCsv = Writer::createFromFileObject(new SplTempFileObject());
            $commissionsCsv->insertOne([
                '#',
                'ID',
                'Business ID',
                'Business Name',
                'Platform',
                'commission Type',
                'Currency',
                'Amount',
                'Remark',
                'Status',
                'Receiver Name',
                'Receiver Bank Name',
                'Receiver Bank Account',
                'Charge IDs',
                'Created Date',
            ]);

            $commissionsData = [];

            $commissions = Commission::with([
                'business' => function (Relation $query) {
                    $query->withTrashed();
                },
            ])->with('charges');

            if ($startDate) {
                $commissions->whereDate('created_at', '>=', $startDate->toDateTimeString());
            }

            if ($endDate) {
                $commissions->whereDate('created_at', '<=', $endDate->toDateTimeString());
            }

            $commissions->whereIn('status', [
                'request_pending',
                'succeeded',
                'succeeded_manually',
            ]);

            $commissions->each(function (Commission $commission) use (&$counter, &$commissionsData) {
                $thisCommission = [
                    ++$counter['commissions'],
                    $commission->id,
                    $commission->business_id,
                    $commission->business->name,
                    $commission->payment_provider,
                    $commission->payment_provider_transfer_type,
                    $commission->currency,
                    getReadableAmountByCurrency($commission->currency, $commission->amount),
                    $commission->remark,
                    $commission->status,
                    $commission->data['account']['name'] ?? null,
                    isset($commission->data['account']['swift_code'])
                        ? Transfer::$availableBankSwiftCodes[$commission->data['account']['swift_code']]
                        ?? $commission->data['account']['swift_code']
                        : null,
                    $commission->data['account']['number'] ?? null,
                    $commission->charges->map(function (Charge $charge) {
                        return 'Charge ID: '.$charge->id.' - '.getFormattedAmount($charge->currency, $charge->amount).' (Commission: '.getFormattedAmount($charge->home_currency, $charge->getCommission()).', REF: '.$charge->plugin_provider_reference.')';
                    })->implode("\r\n"),
                    $commission->created_at->toDateTimeLocalString(),
                ];

                $commissionsData[] = $thisCommission;
            });

            $commissionsCsv->insertAll($commissionsData);

            unset($commissionsData);
        }

        $period = '';

        if ($startDate) {
            $period .= "{$startDate->toDateString()} ";
        } else {
            $period .= "last time ";
        }

        if ($endDate) {
            $period .= "to {$endDate->toDateString()}";
        } else {
            $period .= "to now";
        }

        $now = Date::now();

        $path = 'email-attachments/';
        $path .= $now->toDateString().'/';
        $path .= ($this->option('email') ?? 'aditya@hit-pay.com').'/';

        $uniqueKey = $now->timestamp.str_pad($now->millisecond, 4);

        $path = str_replace(':', '-', $path);

        if (isset($businessCsv)) {
            $businessPath = "{$path}businesses - {$period} ({$uniqueKey}).csv";

            Storage::disk('local')->put($businessPath, $businessCsv->getContent());
        }

        if (isset($chargesCsv)) {
            $chargesPath = "{$path}chargers - {$period} ({$uniqueKey}).csv";

            Storage::disk('local')->put($chargesPath, $chargesCsv->getContent());
        }

        if (isset($transfersCsv)) {
            $transfersPath = "{$path}transfers - {$period} ({$uniqueKey}).csv";

            Storage::disk('local')->put($transfersPath, $transfersCsv->getContent());
        }

        if (isset($refundsCsv)) {
            if ($request !== 'all' && $request !== 'refunds') {
                $path .= $request === 'campaigns' ? 'campaign-cashbacks' : $request;
            } else {
                $path .= 'refunds';
            }

            $refundsPath = "{$path} - {$period} ({$uniqueKey}).csv";

            Storage::disk('local')->put($refundsPath, $refundsCsv->getContent());
        }

        if (isset($commissionsCsv)) {
            $commissionsPath = "{$path}commissions - {$period} ({$uniqueKey}).csv";

            Storage::disk('local')->put($commissionsPath, $commissionsCsv->getContent());
        }

        $this->info('Done');
        $this->info(memory_get_peak_usage());

        $endTime = Date::now();
        $timeTaken = $startTime->diffInSeconds($endTime);

        if ($timeTaken > 120) {
            $details = [];

            foreach ($counter as $key => $value) {
                $details[] = "{$key} : ".number_format($value);
            }

            $details = implode("\n", $details);

            Log::warning("Exporting to admin takes {$timeTaken} seconds to completed.\nSummary\n{$details}");
        }

        try {
            Log::info(__CLASS__.' end exporting '.$request.' for '.$this->option('period'));
        } catch (Throwable $throwable) {
        }
    }
}
