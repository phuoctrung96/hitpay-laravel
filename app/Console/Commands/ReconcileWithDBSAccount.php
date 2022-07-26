<?php

namespace App\Console\Commands;

use App\Business;
use App\Business\Charge;
use App\Business\Commission;
use App\Business\PaymentIntent;
use App\Business\Refund;
use App\Business\Transfer;
use App\Business\Wallet;
use App\Enumerations\Business\ChargeStatus;
use App\Models\Business\Charge\AutoRefund;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades;
use Illuminate\Support\Str;
use League\Csv\Reader;
use League\Csv\Writer;
use SplTempFileObject;
use Throwable;

class ReconcileWithDBSAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dbs:reconcile {filename}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile DBS bank statement.';

    protected Carbon $now;

    protected string $filename;

    protected string $date;

    protected array $dateRange;

    protected string $dateValue;

    protected Collection $rawData;

    protected array $analysedData = [];

    protected array $processedData = [];

    protected int $chunkSize = 1000;

    protected array $notes = [];

    public function __construct()
    {
        parent::__construct();

        $this->now = Facades\Date::now();

        $this->rawData = Collection::make();

        $this->analysedData = [
            'credit' => [
                'DICNP' => Collection::make(),
                'DICNT' => Collection::make(),
                'IRGPP' => Collection::make(),
            ],

            'debit' => [
                'IRGPP' => Collection::make(),
                'IRRFD' => Collection::make(),
            ],
        ];

        $this->processedData = [
            'matchedDICNPsInCharges' => Collection::make(),
            'matchedDICNPsInPaymentIntents' => Collection::make(),
            'matchedDICNTsInTopUps' => Collection::make(),

            'incomingGrabPays' => Collection::make(),
            'incomingShopeePays' => Collection::make(),
            'incomingZips' => Collection::make(),

            'unknownIncoming' => Collection::make(),

            'matchedFastPayoutsInTransfers' => Collection::make(),
            'matchedFastPayoutsInCommissions' => Collection::make(),
            'matchedMaxRefundsInRefunds' => Collection::make(),
            'matchedMaxRefundsInAutoRefunds' => Collection::make(),
            'matchedMaxRefundsInCashback' => Collection::make(),
            'matchedMaxRefundsInCampaignCashback' => Collection::make(),

            'fastPayoutServiceFees' => Collection::make(),

            'unknownOutgoing' => Collection::make(),
        ];

        if (Facades\App::isLocal()) {
            Facades\DB::listen(function (QueryExecuted $queryExecuted) {
                try {
                    $bindings = Collection::make($queryExecuted->bindings)->map(function (string $value) : string {
                        return "'{$value}'";
                    })->toArray();

                    Facades\Log::info(Str::replaceArray('?', $bindings, $queryExecuted->sql));
                } catch (Throwable $throwable) {
                    Facades\Log::error("Failed to listen to the query. Error: {$throwable->getMessage()}");
                }
            });
        }
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \League\Csv\CannotInsertRecord
     * @throws \League\Csv\Exception
     * @throws \ReflectionException
     */
    public function handle() : int
    {
        $this->analyseRawData();

        $this->processCreditDICNP();
        $this->processCreditDICNT();
        $this->processDebitIRGPP();
        $this->processDebitIRRFD();

        $this->analyseOutcome();

        // export breakdown for GrabPay
        // export breakdown for ShopeePay
        // export breakdown for Zip

        $this->finalizeOthers();

        $this->summarize();

        return 0;
    }

    /**
     * @return void
     * @throws \League\Csv\Exception
     * @throws \ReflectionException
     */
    protected function analyseRawData() : void
    {
        $this->filename = $this->argument('filename');

        // change to file? or download to local first.
        //
        $file = Facades\Storage::get("reconciliations/dbs/raw/{$this->filename}.csv");

        $csv = Reader::createFromString($file);
        $csv = $csv->setHeaderOffset(0)->skipEmptyRecords();

        // `$content` structure
        // --------------------
        //
        // array:7 [
        //     "Date" => "11-Apr-2022"
        //     "Value Date" => "11-Apr-2022"
        //     "Transaction Description 1" => "Inward PayNow"
        //     "Transaction Description 2" => "DICNP16496909424467RLLMWS IMB1022907754650000000C724568727812 OTHER CHAN SIEW PING (ZENG XIUBING) SGD 126"
        //     "Debit" => ""
        //     "Credit" => "126.00"
        //     "Running Balance" => "353,821.79"
        // ]
        //
        foreach ($csv->getRecords() as $line => $content) {
            $this->process($line, $content);
        }
    }

    /**
     * @param  int  $line
     * @param  array  $content
     *
     * @return void
     * @throws \ReflectionException
     */
    protected function process(int $line, array $content) : void
    {
        if (!preg_match('/^\d{2}-\w{3}-\d{4}/', $content['Date'])) {
            throw new Exception("Invalid format, check line {$line}");
        }

        $date = Carbon::createFromFormat('d-M-Y', $content['Date'])->toDateString();

        if (!isset($this->date)) {
            $this->date = $date;
            $this->dateRange = [
                "{$this->date} 00:00:00",
                "{$this->date} 23:59:59",
            ];
        } elseif ($this->date !== $date) {
            throw new Exception('All the transactions must be on the same day.');
        }

        // TODO - KIV - Not sure what's the purpose of value date.
        //
        $dateValue = Carbon::createFromFormat('d-M-Y', $content['Value Date'])->toDateString();

        if ($date !== $dateValue) {
            return;
        }

        if (!isset($this->dateValue)) {
            $this->dateValue = $dateValue;
        } elseif ($this->dateValue !== $dateValue) {
            throw new Exception('All the transactions must be on the same day.');
        }

        $isCrediting = !blank($content['Credit']);

        $amount = $isCrediting ? $content['Credit'] : $content['Debit'];
        $amount = str_replace(',', '', $amount);

        if (!is_numeric($amount)) {
            throw new Exception("The amount is invalid at line {$line}.");
        }

        $originalContent = $content;

        $content = [
            'line' => $line,
            'date' => $this->date,
            'date_value' => $this->dateValue,
            'description_1' => $content['Transaction Description 1'],
            'description_2' => $content['Transaction Description 2'],
            'debit' => $isCrediting ? null : $content['Debit'],
            'debit_minor' => $isCrediting ? null : getRealAmountForCurrency('sgd', $amount),
            'credit' => $isCrediting ? $content['Credit'] : null,
            'credit_minor' => $isCrediting ? getRealAmountForCurrency('sgd', $amount) : null,
        ];

        $this->rawData->push($content);

        $content['original'] = $originalContent;

        if ($isCrediting) {
            if (Str::startsWith($content['description_2'], 'DICNP')) {
                $content['hitpay_reference'] = substr($content['description_2'], 0, 25);

                $this->analysedData['credit']['DICNP']->push($content);
            } elseif (Str::startsWith($content['description_2'], 'DICNT')) {
                $content['hitpay_reference'] = substr($content['description_2'], 0, 25);

                $this->analysedData['credit']['DICNT']->push($content);
            } elseif (Str::startsWith($content['description_2'], 'GPMEX Hitpay')) {
                $this->processedData['incomingGrabPays']->push($content);
            } elseif (Str::contains($content['description_2'], 'SHOPEEPAY')) {
                $this->processedData['incomingShopeePays']->push($content);
            } elseif (Str::startsWith($content['description_2'], 'ZIPGLOBALCONSULTING')) {
                $this->processedData['incomingZips']->push($content);
            } else {
                $response = $this->isRecognizedOutgoing($content['description_2']);

                if (is_array($response)) {
                    if ($response[0] === 'fast_payout') {
                        if ($this->analysedData['debit']['IRGPP']->where('description_2', $content['description_2'])) {
                            $this->analysedData['credit']['IRGPP']->push($content);

                            $content['suspected'] = 'Fast Payout Reverted';
                        }
                    }
                }

                $this->processedData['unknownIncoming']->push($content);
            }
        } else {
            $response = $this->isRecognizedOutgoing($content['description_2']);

            if (!is_array($response)) {
                $this->processedData['unknownOutgoing']->push($content);
            } elseif ($response[0] === 'fast_payout') {
                $content['hitpay_reference'] = $response[1];

                if ($content['description_1'] === 'SERVICE CHARGE FOR FAST PAYMENT') {
                    $this->processedData['fastPayoutServiceFees']->push($content);
                } else {
                    $this->analysedData['debit']['IRGPP']->push($content);
                }
            } elseif ($response[0] === 'max_refund') {
                $content['hitpay_reference'] = $response[1];

                $this->analysedData['debit']['IRRFD']->push($content);
            } else {
                $this->processedData['unknownOutgoing']->push($content);
            }
        }

        $date = str_replace('-', '/', $this->date);

        $targetOriginalFilename = "reconciliations/dbs/processed/{$date}/original.csv";

        if (Facades\Storage::exists($targetOriginalFilename)) {
            Facades\Storage::delete($targetOriginalFilename);
        }

        Facades\Storage::copy("reconciliations/dbs/raw/{$this->filename}.csv", $targetOriginalFilename);
    }

    /**
     * @return void
     */
    protected function processCreditDICNP() : void
    {
        /**
         * @var \Illuminate\Support\Collection $creditDICNP
         */
        $creditDICNP = $this->analysedData['credit']['DICNP'];

        $creditDICNP
            ->chunk($this->chunkSize)
            ->each(function (Collection $collection, int $index) : void {
                $index += 1;

                $this->line("Process DICNP - Chunk #{$index} (size : {$collection->count()})");

                $charges = Charge::query()
                    ->with('paymentIntents')
                    ->where('payment_provider_charge_type', 'inward_credit_notification')
                    ->whereIn('payment_provider_charge_id', $collection->pluck('hitpay_reference')->toArray())
                    ->get();

                $collection->each(function (array $content) use ($charges) : void {
                    $detected = $charges
                        ->where('payment_provider_charge_id', $content['hitpay_reference'])
                        ->whereIn('status', [
                            ChargeStatus::SUCCEEDED,
                            ChargeStatus::REFUNDED,
                        ]);

                    $content['charges'] = $detected;

                    $this->processedData['matchedDICNPsInCharges']->push($content);
                });
            });

        $creditDICNP = $creditDICNP->whereNotIn('line', $this->processedData['matchedDICNPsInCharges']->pluck('line'));

        $creditDICNP
            ->chunk($this->chunkSize)
            ->each(function (Collection $collection, int $index) : void {
                $index += 1;

                $this->line("PayNow Payments (Charge Not Found / Succeeded / Refund) - #{$index} (size : {$collection->count()})");

                $paymentIntents = PaymentIntent::query()
                    ->with('charge')
                    ->where('payment_provider_object_type', 'inward_credit_notification')
                    ->whereIn('payment_provider_object_id', $collection->pluck('hitpay_reference')->toArray())
                    ->get();

                $collection->each(function (array $content) use ($paymentIntents) : void {
                    $detected = $paymentIntents->where('payment_provider_object_id', $content['hitpay_reference']);

                    $content['payment_intents'] = $detected;

                    $this->processedData['matchedDICNPsInPaymentIntents']->push($content);
                });
            });

        $creditDICNP = $creditDICNP
            ->whereNotIn('line', $this->processedData['matchedDICNPsInPaymentIntents']->pluck('line'));

        $this->processedData['unknownIncoming']->merge($creditDICNP);
    }

    /**
     * @return void
     */
    protected function processCreditDICNT() : void
    {
        /**
         * @var \Illuminate\Support\Collection $creditDICNT
         */
        $creditDICNT = $this->analysedData['credit']['DICNT'];

        $creditDICNT
            ->chunk($this->chunkSize)
            ->each(function (Collection $collection, int $index) : void {
                $index += 1;

                $this->line("PayNow Top Ups - #{$index} (size : {$collection->count()})");

                $topUpIntents = Wallet\TopUpIntent::query()
                    ->where('payment_provider_object_type', 'inward_credit_notification')
                    ->whereIn('payment_provider_object_id', $collection->pluck('hitpay_reference')->toArray())
                    ->get();

                $collection->each(function (array $content) use ($topUpIntents) : void {
                    $detected = $topUpIntents->where('payment_provider_object_id', $content['hitpay_reference']);

                    if ($detected->isEmpty()) {
                        return;
                    }

                    $content['top_up_intents'] = $detected;

                    $this->processedData['matchedDICNTsInTopUps']->push($content);
                });
            });

        $creditDICNT = $creditDICNT->whereNotIn('line', $this->processedData['matchedDICNTsInTopUps']->pluck('line'));

        $this->processedData['unknownIncoming']->merge($creditDICNT);
    }

    /**
     * @return void
     */
    protected function processDebitIRGPP() : void
    {
        /**
         * @var \Illuminate\Support\Collection $debitIRGPP
         */
        $debitIRGPP = $this->analysedData['debit']['IRGPP'];

        $debitIRGPP
            ->chunk($this->chunkSize)
            ->each(function (Collection $collection, int $index) : void {
                $index += 1;

                $this->line("Fast Payouts - #{$index} (size : {$collection->count()})");

                $transfers = Transfer::query()->findMany($collection->pluck('hitpay_reference')->toArray());

                $collection->each(function (array $content) use ($transfers) : void {
                    $detected = $transfers->find($content['hitpay_reference']);

                    if ($detected instanceof Transfer) {
                        $content['transfer'] = $detected;

                        $this->processedData['matchedFastPayoutsInTransfers']->push($content);
                    }
                });
            });

        $linesOfMatchedFastPayoutsInTransfers = $this->processedData['matchedFastPayoutsInTransfers']->pluck('line');

        $debitIRGPP = $debitIRGPP->whereNotIn('line', $linesOfMatchedFastPayoutsInTransfers);

        // TODO - Process commissions here.

        $this->processedData['unknownOutgoing']->merge($debitIRGPP);
    }

    /**
     * @return void
     */
    protected function processDebitIRRFD() : void
    {
        /**
         * @var \Illuminate\Support\Collection $debitIRRFD
         */
        $debitIRRFD = $this->analysedData['debit']['IRRFD'];

        $debitIRRFD
            ->chunk($this->chunkSize)
            ->each(function (Collection $collection, int $index) : void {
                $index += 1;

                $this->line("Max Refunds - #{$index} (size : {$collection->count()})");

                $refunds = Refund::query()->with('charge')->findMany($collection->pluck('hitpay_reference')->toArray());

                $collection->each(function (array $content) use ($refunds) : void {
                    $detected = $refunds->find($content['hitpay_reference']);

                    if ($detected instanceof Refund) {
                        $content['refund'] = $detected;

                        if ($detected->is_cashback) {
                            $this->processedData['matchedMaxRefundsInCashback']->push($content);
                        } elseif ($detected->is_campaign_cashback) {
                            $this->processedData['matchedMaxRefundsInCampaignCashback']->push($content);
                        } else {
                            $this->processedData['matchedMaxRefundsInRefunds']->push($content);
                        }
                    }
                });
            });

        $debitIRRFD = $debitIRRFD
            ->whereNotIn('line', $this->processedData['matchedMaxRefundsInCashback']->pluck('line'))
            ->whereNotIn('line', $this->processedData['matchedMaxRefundsInCampaignCashback']->pluck('line'))
            ->whereNotIn('line', $this->processedData['matchedMaxRefundsInRefunds']->pluck('line'));

        $debitIRRFD
            ->chunk($this->chunkSize)
            ->each(function (Collection $collection, int $index) : void {
                $index += 1;

                $this->line("Auto Refunds - #{$index} (size : {$collection->count()})");

                $autoRefunds = AutoRefund::query()
                    ->with([ 'charge', 'paymentIntent' ])
                    ->findMany($collection->pluck('hitpay_reference')->toArray());

                $collection->each(function (array $content) use ($autoRefunds) : void {
                    $detected = $autoRefunds->find($content['hitpay_reference']);

                    if ($detected instanceof AutoRefund) {
                        $content['auto_refund'] = $detected;

                        $this->processedData['matchedMaxRefundsInAutoRefunds']->push($content);
                    }
                });
            });

        $debitIRRFD = $debitIRRFD
            ->whereNotIn('line', $this->processedData['matchedMaxRefundsInAutoRefunds']->pluck('line'));

        $this->processedData['unknownOutgoing']->merge($debitIRRFD);
    }

    protected function analyseOutcome() : void
    {
        // Outgoing

        // Debited IRGPPs
        //
        $sumDebitIRGPPs = $this->analysedData['debit']['IRGPP']->sum('debit_minor');

        $sumMatchedFastPayoutsInTransfers = $this->processedData['matchedFastPayoutsInTransfers']->sum('debit_minor');
        $sumMatchedFastPayoutsInCommissions =
            $this->processedData['matchedFastPayoutsInCommissions']->sum('debit_minor');

        // $sumMatchedFastPayouts = $sumMatchedFastPayoutsInTransfers + $sumMatchedFastPayoutsInCommissions;

        // Debited IRRFDs
        //
        $sumDebitIRRFDs = $this->analysedData['debit']['IRRFD']->sum('debit_minor');

        $sumMatchedMaxRefundsInAutoRefunds = $this->processedData['matchedMaxRefundsInAutoRefunds']->sum('debit_minor');
        $sumMatchedMaxRefundsInCampaignCashback =
            $this->processedData['matchedMaxRefundsInCampaignCashback']->sum('debit_minor');
        $sumMatchedMaxRefundsInCashback = $this->processedData['matchedMaxRefundsInCashback']->sum('debit_minor');
        $sumMatchedMaxRefundsInRefunds = $this->processedData['matchedMaxRefundsInRefunds']->sum('debit_minor');

        // $sumMatchedMaxRefunds = $sumMatchedMaxRefundsInAutoRefunds
        //     + $sumMatchedMaxRefundsInCampaignCashback
        //     + $sumMatchedMaxRefundsInCashback
        //     + $sumMatchedMaxRefundsInRefunds;

        // Incoming

        // Credited DICNPs
        //
        $sumCreditDICNPs = $this->analysedData['credit']['DICNP']->sum('credit_minor');

        $sumMatchedDICNPsInCharges = $this->processedData['matchedDICNPsInCharges']->sum('credit_minor');

        $sumMatchedDICNPsInPaymentIntents = $this->processedData['matchedDICNPsInPaymentIntents']->sum('credit_minor');

        // $sumMatchedDICNPs = $sumMatchedDICNPsInCharges + $sumMatchedDICNPsInPaymentIntents;

        // Credited DICNTs
        //
        $sumCreditDICNTs = $this->analysedData['credit']['DICNT']->sum('credit_minor');

        $sumMatchedDICNTsInTopUps = $this->processedData['matchedDICNTsInTopUps']->sum('credit_minor');

        // $sumMatchedDICNTs = $sumMatchedDICNTsInTopUps;

        // Credited IRGPPs (Revert of IRGPPs)
        //
        $sumCreditIRGPPs = $this->analysedData['credit']['IRGPP']->sum('credit_minor');

        // Service Fee of IRGPPs
        //
        $sumFastPayoutsServiceFees = $this->processedData['fastPayoutServiceFees']->sum('debit_minor');

        // HitPay Charges
        //
        $chargesInHitPay = Charge::query()
            ->with([
                'receiptRecipients',
                'business' => function (Relations\BelongsTo $query) {
                    $query->withTrashed();
                },
            ])
            ->join(
                'business_payment_intents',
                'business_payment_intents.business_charge_id',
                '=',
                'business_charges.id'
            )
            ->whereIn('business_charges.status', [ ChargeStatus::SUCCEEDED, ChargeStatus::REFUNDED ])
            ->where('business_charges.payment_provider', 'dbs_sg')
            ->where('business_charges.payment_provider_charge_type', 'inward_credit_notification')
            ->where('business_payment_intents.status', 'succeeded')
            ->whereBetween('business_payment_intents.updated_at', $this->dateRange)
            ->get();

        $sumChargesInHitPay = $chargesInHitPay->sum('amount');

        // HitPay Auto Refunds
        //
        // Currently, auto refund is only happens to the PayNow ICN, so no additional filters other than date
        // required for now.
        //
        $autoRefundsInHitPay = AutoRefund::query()
            ->with([
                'charge',
                'business' => function (Relations\BelongsTo $query) {
                    $query->withTrashed();
                },
                'paymentIntent',
            ])
            ->whereBetween('refunded_at', $this->dateRange)
            ->get();

        $sumAutoRefundsInHitPay = $autoRefundsInHitPay->sum('amount');

        // HitPay Top-Ups
        //
        $topUpsInHitPay = Wallet\TopUpIntent::query()
            ->with([
                'business' => function (Relations\BelongsTo $query) {
                    $query->withTrashed();
                },
            ])
            ->where('payment_provider', 'dbs_sg')
            ->where('payment_provider_object_type', 'inward_credit_notification')
            ->where('status', 'succeeded')
            ->whereBetween('updated_at', $this->dateRange)
            ->get();

        $sumTopUpsInHitPay = $topUpsInHitPay->sum('amount');

        // HitPay Refunds, Cashback and Campaign Cashback
        //
        // We get all the refunds out between the date range, and filter by types later.
        //
        $recordsInHitPay = Refund::query()
            ->with([
                'charge' => function (Relations\BelongsTo $query) {
                    $query->with([
                        'business' => function (Relations\BelongsTo $query) {
                            $query->withTrashed();
                        },
                    ]);
                },
            ])
            ->where('payment_provider', 'dbs_sg')
            ->whereBetween('created_at', $this->dateRange)
            ->get();

        $refundsInHitPay = $recordsInHitPay->where('is_cashback', 0)->where('is_campaign_cashback', 0);
        $cashbackInHitPay = $recordsInHitPay->where('is_cashback', 1)->where('is_campaign_cashback', 0);
        $campaignCashbackInHitPay = $recordsInHitPay->where('is_cashback', 0)->where('is_campaign_cashback', 1);

        $totalGroupedRecords = $refundsInHitPay->count()
            + $cashbackInHitPay->count()
            + $campaignCashbackInHitPay->count();

        if ($recordsInHitPay->count() !== $totalGroupedRecords) {
            throw new Exception('Something wrong with the refunds data in database, the count are not tally.');
        }

        $sumRefundsInHitPay = $refundsInHitPay->sum('amount');
        $sumCashbackInHitPay = $cashbackInHitPay->sum('amount');
        $sumCampaignCashbackInHitPay = $campaignCashbackInHitPay->sum('amount');

        // HitPay Transfers a.k.a. Payouts
        //
        $transferInHitPay = Transfer::query()
            ->with([
                'charges',
                'business' => function (Relations\BelongsTo $query) {
                    $query->withTrashed();
                },
            ])
            ->where('payment_provider', 'dbs_sg')
            ->whereIn('status', [
                'succeeded',
                'succeeded_manually',
            ])
            ->whereBetween('transferred_at', $this->dateRange)
            ->get();

        $sumTransfersInHitPay = $transferInHitPay->sum('amount');

        // HitPay Commissions
        //
        $commissionsInHitPay = Commission::query()
            ->with([
                'business' => function (Relations\BelongsTo $query) {
                    $query->withTrashed();
                },
            ])
            ->whereIn('status', [
                'succeeded',
                'succeeded_manually',
            ])
            ->whereBetween('created_at', $this->dateRange)
            ->get();

        $sumCommissionsInHitPay = $commissionsInHitPay->sum('amount');

        //////////

        $creditTotal = $this->rawData->sum('credit_minor');

        $credit['dicnps'] = $sumCreditDICNPs;
        $credit['dicnts'] = $sumCreditDICNTs;
        $credit['grabpays'] = $this->processedData['incomingGrabPays']->sum('credit_minor');
        $credit['shopeepays'] = $this->processedData['incomingShopeePays']->sum('credit_minor');
        $credit['zips'] = $this->processedData['incomingZips']->sum('credit_minor');
        $credit['unknown'] = $this->processedData['unknownIncoming']->sum('credit_minor');

        $creditTally = $creditTotal;

        foreach ($credit as $item) {
            $creditTally -= $item;
        }

        $credit['*'] = $creditTotal;

        $data['bank']['credit'] = $credit;

        $debitTotal = $this->rawData->sum('debit_minor');

        $debit['irrfds'] = $sumDebitIRRFDs;
        $debit['irgpps'] = $sumDebitIRGPPs;
        $debit['irgpp_fees'] = $sumFastPayoutsServiceFees;
        $debit['unknown'] = $this->processedData['unknownOutgoing']->sum('debit_minor');

        $debitTally = $debitTotal;

        foreach ($debit as $item) {
            $debitTally -= $item;
        }

        $debit['*'] = $debitTotal;

        $data['bank']['debit'] = $debit;

        $data['checked']['bank']['credit'] = $creditTally;
        $data['checked']['bank']['debit'] = $debitTally;

        $data['hitpay']['charges'] = $sumChargesInHitPay;
        // $data['hitpay']['payment_intents'] = $sumMatchedDICNPsInPaymentIntents;
        $data['hitpay']['top_ups'] = $sumTopUpsInHitPay;
        $data['hitpay']['auto_refunds'] = $sumAutoRefundsInHitPay;
        $data['hitpay']['cashback'] = $sumCashbackInHitPay;
        $data['hitpay']['campaign_cashback'] = $sumCampaignCashbackInHitPay;
        $data['hitpay']['refunds'] = $sumRefundsInHitPay;
        $data['hitpay']['transfers'] = $sumTransfersInHitPay;
        $data['hitpay']['commissions'] = $sumCommissionsInHitPay;

        $data['suspected']['irgpps_reverted'] = $sumCreditIRGPPs;

        $data['analysed']['charges'] = $sumMatchedDICNPsInCharges;
        $data['analysed']['payment_intents'] = $sumMatchedDICNPsInPaymentIntents;
        $data['analysed']['top_ups'] = $sumMatchedDICNTsInTopUps;
        $data['analysed']['auto_refunds'] = $sumMatchedMaxRefundsInAutoRefunds;
        $data['analysed']['cashback'] = $sumMatchedMaxRefundsInCashback;
        $data['analysed']['campaign_cashback'] = $sumMatchedMaxRefundsInCampaignCashback;
        $data['analysed']['refunds'] = $sumMatchedMaxRefundsInRefunds;
        $data['analysed']['transfers'] = $sumMatchedFastPayoutsInTransfers;
        $data['analysed']['commissions'] = $sumMatchedFastPayoutsInCommissions;

        $data['checked']['hitpay']['charges'] = $sumCreditDICNPs - ( $sumChargesInHitPay + $sumAutoRefundsInHitPay );
        $data['checked']['hitpay']['top_ups'] = $sumCreditDICNTs - $sumTopUpsInHitPay;

        $data['checked']['hitpay']['transfers'] = $sumMatchedFastPayoutsInTransfers - $sumTransfersInHitPay;
        $data['checked']['hitpay']['commissions'] = $sumMatchedFastPayoutsInCommissions - $sumCommissionsInHitPay;

        $data['checked']['hitpay']['auto_refunds'] = $sumMatchedMaxRefundsInAutoRefunds - $sumAutoRefundsInHitPay;
        $data['checked']['hitpay']['cashback'] = $sumMatchedMaxRefundsInCashback - $sumCashbackInHitPay;
        $data['checked']['hitpay']['campaign_cashback'] =
            $sumMatchedMaxRefundsInCampaignCashback - $sumCampaignCashbackInHitPay;
        $data['checked']['hitpay']['refunds'] = $sumMatchedMaxRefundsInRefunds - $sumRefundsInHitPay;
        $data['checked']['suspected']['irgpps_reverted'] = $sumMatchedFastPayoutsInTransfers
            - ( $sumTransfersInHitPay + $sumCreditIRGPPs );

        // If all check is zero = Very good, export the remaining.
        //
        $date = str_replace('-', '/', $this->date);

        Facades\Storage::put("reconciliations/dbs/processed/{$date}/summary.json", json_encode($data));

        $counter = 0;

        $topUpsInHitPay = $topUpsInHitPay->map(function (Wallet\TopUpIntent $intent) use (&$counter) : array {
            return [
                ++$counter,
                $intent->getKey(),
                $intent->business_id,
                $intent->business ? $intent->business->name : '',
                $intent->payment_provider,
                $intent->payment_provider_object_type,
                $intent->payment_provider_object_id,
                $intent->payment_provider_method,
                $intent->status,
                strtoupper($intent->currency),
                getReadableAmountByCurrency($intent->currency, $intent->amount),
                $intent->updated_at->toDateTimeString(),
            ];
        });

        $this->writeCsv('top-ups', [
            '#',
            'ID',
            'Business ID',
            'Business Name',
            'Platform',
            'Platform Object Type',
            'Platform Object ID',
            'Method',
            'Status',
            'Currency',
            'Amount',
            'Created Date',
        ], $topUpsInHitPay->all(), $topUpsInHitPay->isEmpty(), 'HITPAY');

        $counter = 0;

        $chargesInHitPay = $chargesInHitPay->map(function (Charge $charge) use (&$counter) : array {
            $emails = $charge->receiptRecipients->pluck('email');

            $emails->push($charge->customer_email);

            $thisCharge = [
                ++$counter,
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
            } elseif (( $charge->payment_provider_transfer_type === 'wallet'
                    || $charge->payment_provider_transfer_type === 'destination'
                    || $charge->payment_provider_transfer_type === 'manual' )
                && $charge->home_currency) {
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

            return $thisCharge;
        });

        $this->writeCsv('charges', [
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
        ], $chargesInHitPay->all(), $chargesInHitPay->isEmpty(), 'HITPAY');

        $counter = 0;

        $commissionsInHitPay = $commissionsInHitPay->map(function (Commission $commission) use (&$counter) : array {
            if (isset($commission->data['account']['swift_code'])) {
                $swiftCode = Transfer::$availableBankSwiftCodes[$commission->data['account']['swift_code']] ??
                    $commission->data['account']['swift_code'];
            }

            return [
                ++$counter,
                $commission->id,
                $commission->business_id,
                $commission->business ? $commission->business->name : '',
                $commission->payment_provider,
                $commission->payment_provider_transfer_type,
                $commission->currency,
                getReadableAmountByCurrency($commission->currency, $commission->amount),
                $commission->remark,
                $commission->status,
                $commission->data['account']['name'] ?? null,
                $swiftCode ?? null,
                $commission->data['account']['number'] ?? null,
                $commission->charges->map(function (Charge $charge) {
                    $chargeAmount = getFormattedAmount($charge->currency, $charge->amount);
                    $commissionAmount = getFormattedAmount($charge->home_currency, $charge->getCommission());

                    return "Charge ID: {$charge->id} - {$chargeAmount} (Commission: {$commissionAmount}, REF: {$charge->plugin_provider_reference})";
                })->implode("\r\n"),
                $commission->created_at->toDateTimeString(),
            ];
        });

        $this->writeCsv('commissions', [
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
        ], $commissionsInHitPay->all(), $commissionsInHitPay->isEmpty(), 'HITPAY');

        $counter = 0;

        $transferInHitPay = $transferInHitPay->map(function (Transfer $transfer) use (&$counter) : array {
            $referenceId = str_replace('-', '', $transfer->id);
            $status = $transfer->status;

            if (is_int($transfer->counter)) {
                $referenceId = "{$referenceId}-{$transfer->counter}";

                if ($status === 'succeeded') {
                    $status = "{$status}_retried";

                    // We add 1 here because if succeeded after retried, it will not update the counter. E.g. payment
                    // failed at first time, it will set counter to 0. If after retried it succeeded, it will still 0,
                    // but status succeeded.
                    //
                    $_counter = $transfer->counter + 1;
                }
            }

            $failedReason = null;

            if (!Str::startsWith($status, 'succeeded')) {
                if (isset($transfer->data['requests'])) {
                    $requestsData = collect($transfer->data['requests']);

                    foreach ($requestsData->pluck('response.txnResponse')->where('txnStatus', 'RJCT') as $response) {
                        $timestamp = $response['txnSettlementDt'] ?? 'Unknown Time';
                        $rejectCode = $response['txnRejectCode'] ?? 'Unknown Code';
                        $reason = $response['txnStatusDescription'] ?? 'Unknown Reason';

                        $failedReasons[] = "{$timestamp} - [{$rejectCode}] {$reason}";
                    }

                    if (isset($failedReasons)) {
                        $failedReason = implode("\n", $failedReasons);
                    } else {
                        $failedReason = 'No failed response logged. Check with backend.';
                    }
                } else {
                    $failedReason = 'No request found for pending transfer. Check with backend.';
                }
            }

            $readableAmount = getReadableAmountByCurrency($transfer->currency, $transfer->amount);

            if (isset($transfer->data['account']['swift_code'])) {
                $swiftCode = $transfer->data['account']['swift_code'];

                $bankName = Transfer::$availableBankSwiftCodes[$swiftCode] ?? $swiftCode;
            }

            $chargeIds = $transfer->charges->map(function (Charge $charge) {
                $formattedAmount = getFormattedAmount($charge->currency, $charge->amount);

                $netAmount = $charge->home_currency_amount - $charge->getTotalFee();

                $formattedNetAmount = getFormattedAmount($charge->home_currency, $netAmount);

                return "Charge ID: {$charge->id} - {$formattedAmount} (Net: {$formattedNetAmount})";
            })->implode("\r\n");

            if ($transfer->transferred_at instanceof \Carbon\Carbon) {
                $transferredAt = $transfer->transferred_at->toDateTimeString();
            }

            return [
                ++$counter,
                $transfer->id,
                $referenceId,
                $transfer->business_id,
                $transfer->business ? $transfer->business->name : '',
                $transfer->payment_provider,
                $transfer->payment_provider_transfer_type,
                $transfer->currency,
                $readableAmount,
                $transfer->remark,
                $status,
                $failedReason,
                $_counter ?? null,
                $transfer->data['account']['name'] ?? null,
                $bankName ?? null,
                $transfer->data['account']['number'] ?? null,
                $chargeIds,
                $transferredAt ?? null,
            ];
        });

        $this->writeCsv('payouts', [
            '#',
            'ID',
            'DBS ID',
            'Business ID',
            'Business Name',
            'Platform',
            'Transfer Type',
            'Currency',
            'Amount',
            'Remark',
            'Status',
            'Failed Reason',
            'Retried',
            'Receiver Name',
            'Receiver Bank Name',
            'Receiver Bank Account',
            'Charge IDs',
            'Created Date',
        ], $transferInHitPay->all(), $transferInHitPay->isEmpty(), 'HITPAY');

        $counter = 0;

        $autoRefundsInHitPay = $autoRefundsInHitPay->map(function (AutoRefund $autoRefund) use (&$counter) : array {
            return [
                ++$counter,
                $autoRefund->id,
                str_replace('-', '', $autoRefund->id),
                $autoRefund->business_id,
                $autoRefund->business ? $autoRefund->business->name : '',
                $autoRefund->charge->id,
                $autoRefund->paymentIntent->id,
                $autoRefund->charge->customer_name,
                $autoRefund->charge->customer_email,
                $autoRefund->currency,
                getReadableAmountByCurrency($autoRefund->currency, $autoRefund->amount),
                $autoRefund->payment_provider_refund_id,
                $autoRefund->additional_reference,
                $autoRefund->refunded_at,
                $autoRefund->paymentIntent->created_at,
                $autoRefund->paymentIntent->updated_at,
            ];
        });

        $this->writeCsv('auto-refunds', [
            '#',
            'Auto Refund ID',
            'DBS ID',
            'Business ID',
            'Business Name',
            'Charge ID',
            'Payment Intent ID',
            'Customer Name',
            'Customer Email',
            'Currency',
            'Amount',
            'Refund ID',
            'Transaction Reference ID',
            'Auto Refunded Date',
            'Payment Intent Created Date',
            'Payment Intent Last Updated Date',
        ], $autoRefundsInHitPay->all(), $autoRefundsInHitPay->isEmpty(), 'HITPAY');

        $this->writeCsvForRefunds('refunds', $refundsInHitPay);
        $this->writeCsvForRefunds('campaign-cashback', $campaignCashbackInHitPay);
        $this->writeCsvForRefunds('cashback', $cashbackInHitPay);
    }

    protected function writeCsvForRefunds(string $filename, Eloquent\Collection $collection) : void
    {
        $counter = 0;

        $collection = $collection->map(function (Refund $refund) use (&$counter) : array {
            if ($refund->charge instanceof Charge) {
                $businessId = $refund->charge->business_id;

                if ($refund->charge->business instanceof Business) {
                    $businessName = $refund->charge->business->name;
                }

                $chargeCurrency = $refund->charge->currency;
                $chargeAmount = getReadableAmountByCurrency($refund->charge->currency, $refund->charge->amount);
                $refundAmount = getReadableAmountByCurrency($refund->charge->currency, $refund->amount);
            }

            return [
                ++$counter,
                $refund->id,
                str_replace('-', '', $refund->id),
                $refund->business_charge_id,
                $businessId ?? '',
                $businessName ?? '',
                $refund->payment_provider,
                $refund->payment_provider_refund_type,
                $refund->payment_provider_refund_id,
                $chargeCurrency ?? '',
                $chargeAmount ?? '',
                $refundAmount ?? "{$refund->amount} (Charge Not Found)",
                $refund->created_at->toDateTimeString(),
            ];
        });

        $this->writeCsv($filename, [
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
        ], $collection->all(), $collection->isEmpty(), 'HITPAY');
    }

    /**
     * @return void
     * @throws \League\Csv\CannotInsertRecord
     */
    protected function finalizeOthers() : void
    {
        $this->generateCSVWithOriginal('credit-dicnp', $this->analysedData['credit']['DICNP']);
        $this->generateCSVWithOriginal('credit-dicnt', $this->analysedData['credit']['DICNT']);
        $this->generateCSVWithOriginal('debit-irgpp', $this->analysedData['debit']['IRGPP']);
        $this->generateCSVWithOriginal('debit-irrfd', $this->analysedData['debit']['IRRFD']);

        $this->generateCSVWithOriginal('incoming-grabpay', $this->processedData['incomingGrabPays']);
        $this->generateCSVWithOriginal('incoming-shopeepay', $this->processedData['incomingShopeePays']);
        $this->generateCSVWithOriginal('incoming-zips', $this->processedData['incomingZips']);
        $this->generateCSVWithOriginal('outgoing-fast-payouts-service-fees',
            $this->processedData['fastPayoutServiceFees']);
        $this->generateCSVWithOriginal('unknown-incoming', $this->processedData['unknownIncoming']);
        $this->generateCSVWithOriginal('unknown-outgoing', $this->processedData['unknownOutgoing']);
    }

    /**
     * @param  string  $filename
     * @param  string  $name
     *
     * @return void
     * @throws \League\Csv\CannotInsertRecord
     */
    protected function generateCSVWithOriginal(string $filename, Collection $data) : void
    {
        $first = $data->first();

        $empty = is_null($first);

        $header = $empty ? [] : array_keys($first['original']);

        $this->writeCsv($filename, $header ?? [], $data->pluck('original')->toArray(), $empty, 'ORIGINAL');
    }

    /**
     * @param  string  $string
     *
     * @return array|false
     */
    protected function isRecognizedOutgoing(string $string)
    {
        if (Str::contains($string, 'IRGPP')) {
            $type = 'fast_payout';

            goto check;
        }

        if (Str::contains($string, 'IRRFD')) {
            $type = 'max_refund';

            goto check;
        }

        return false;

        check:

        $segments = explode(' ', $string);

        $expected = $segments[0];

        $length = strlen($expected);

        if ($length === 35) {
            $expected = explode('-', $expected)[0];

            $length = strlen($expected);
        }

        if ($length !== 32) {
            return false;
        }

        $uuid = recoverDashForUuid($expected);

        if ($uuid === false) {
            return false;
        }

        return [ $type, $uuid ];
    }

    /**
     * @param  string  $filename
     * @param  array  $header
     * @param  array  $content
     *
     * @return void
     * @throws \League\Csv\CannotInsertRecord
     */
    protected function writeCsv(string $filename, array $header, array $content, bool $empty, string $prefix) : void
    {
        // $this->line('');
        // $this->alert(ucwords(str_replace('-', ' ', $filename)));

        $this->table($header, $content);

        $csv = Writer::createFromFileObject(new SplTempFileObject);

        $csv->insertOne($empty ? [] : $header);

        $csv->insertAll($content);

        $date = str_replace('-', '/', $this->date);

        $filename = "{$this->date}-{$filename}";

        if ($empty) {
            $filename = "[empty] $filename";
        }

        $filename = "[{$prefix}] $filename";

        Facades\Storage::put("reconciliations/dbs/processed/{$date}/{$filename}.csv", $csv->getContent());
    }

    /**
     * @return void
     */
    protected function summarize()
    {
        $bytes = memory_get_peak_usage();

        for ($i = 0; $bytes >= 1024; $i++) {
            $bytes /= 1024;
        }

        $memory = round($bytes, 2).' '.[ 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' ][$i];

        $summary = [ [ 'Total', $this->rawData->count(), '', '' ] ];

        $count = 0;

        foreach ($this->processedData as $key => $value) {
            $currentCount = $value->count();

            $summary[] = [
                Str::ucfirst($key),
                $currentCount,
                $this->makeReadable($value->sum('debit_minor')),
                $this->makeReadable($value->sum('credit_minor')),
            ];

            $count += $currentCount;
        }
        // one is missing, find out please.
        $summary[] = [ 'FinalCount', $count, '', '' ];
        $summary[] = [ 'Peak Memory', $memory, '', '' ];

        $this->table([ 'Description', '' ], $summary);
    }

    protected function makeReadable(int $value)
    {
        return getReadableAmountByCurrency('sgd', $value);
    }

    protected function notTallyRemarks(int $value1, int $value2) : string
    {
        if ($value1 === $value2) {
            return '';
        }

        $_value2 = $this->makeReadable($value2);

        return "☓ The amount is not tally, the amount in HitPay is {$_value2}";
    }

    protected function manualCheckRemark(bool $value, string $append = null) : string
    {
        if ($value) {
            return '✓';
        }

        $message = '☓ Please check manually.';

        if (is_string($append)) {
            $message .= " {$append}";
        }

        return $message;
    }
}
