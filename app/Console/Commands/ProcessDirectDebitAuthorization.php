<?php

namespace App\Console\Commands;

use App\Business\RecurringBilling;
use App\Enumerations\Business\PaymentMethodType;
use App\Enumerations\Business\RecurringPlanStatus;
use App\Enumerations\PaymentProvider;
use App\Notifications\NotifySubscriptionViaDdaActivated;
use HitPay\DBS\DirectDebitAuthorizationFileReader;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class ProcessDirectDebitAuthorization extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hitpay:process-direct-debit-authorization';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process the direct debit authorization file sent by DBS.';

    /**
     * Execute the console command.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle() : int
    {
        $storageFilePath = 'direct-debit-authorization';
        $localFilePath = storage_path('temp/direct-debit-authorization');

        foreach (Storage::files($storageFilePath.'/new') as $storageZipFilename) {
            $thisStorageFilename = explode('/', $storageZipFilename);
            $thisStorageFilename = array_pop($thisStorageFilename);

            $localZipFilename = $localFilePath.'/new.zip';

            File::replace($localZipFilename, Storage::get($storageZipFilename));

            $zipArchive = new ZipArchive;

            if ($zipArchive->open($localZipFilename)) {
                $zipArchive->setPassword(Config::get('services.dbs.file_password'));

                if (!$zipArchive->extractTo($localFilePath.'/new')) {
                    Log::critical("$storageZipFilename was downloaded, but cannot be extracted, please check.");
                }

                $processed = false;

                foreach (File::files($localFilePath.'/new') as $localFile) {
                    $thisLocalFilename = explode('/', $localFile);
                    $thisLocalFilename = array_pop($thisLocalFilename);

                    if (Str::startsWith($thisLocalFilename, 'EDDA2.')) {
                        $this->process(File::get($localFile));

                        $processed = true;
                    }

                    File::delete($localFile);
                }

                if (!$processed) {
                    Log::critical("$storageZipFilename was downloaded, but cannot find any file to process, please check.");
                } else {
                    Log::channel('failed-collection')->info("$storageZipFilename was downloaded and processed.");
                }
            } else {
                Storage::move($storageZipFilename, $storageFilePath.'/unprocessable/'.$thisStorageFilename);

                Log::critical("$storageZipFilename was downloaded but cannot be opened, please check.");
            }

            Storage::move($storageZipFilename, $storageFilePath.'/processed/'.$thisStorageFilename);
        }

        return 0;
    }

    public function process(string $content)
    {
        $result = DirectDebitAuthorizationFileReader::process($content)->getRecords();
        $result = Collection::make($result);

        $recurringPlans = RecurringBilling::query()
            ->whereIn('dbs_dda_reference', $result->pluck('direct_debit_authorization_reference'))
            ->get();

        foreach ($result as $record) {
            /** @var RecurringBilling $recurringPlan */
            $recurringPlan = $recurringPlans
                ->where('dbs_dda_reference', $record['direct_debit_authorization_reference'])
                ->first();

            if (!$recurringPlan) {
                Log::channel('failed-collection')->info('DDA "'.$record['direct_debit_authorization_reference']
                    .'" detected but not found in our records. No action taken.');

                continue;
            } elseif ($record['transaction_type'] === 'T') {
                Log::channel('failed-collection')->info('DDA "'.$record['direct_debit_authorization_reference']
                    .'" is terminated. No action taken.');
                // terminate and continue
                continue;
            } elseif ($record['transaction_type'] !== 'C') {
                Log::channel('failed-collection')->info('DDA "'.$record['direct_debit_authorization_reference']
                    .'" detected but the transaction type is "'.$record['transaction_type'].'". Please check.');

                continue;
            } elseif ($recurringPlan->status !== RecurringPlanStatus::SCHEDULED) {
                Log::channel('failed-collection')->info('DDA "'.$record['direct_debit_authorization_reference']
                    .'" detected but in our record the recurring plan is "'.$recurringPlan->status.'".'
                    .' No action taken.');

                continue;
            }

            $code = Str::limit($record['receiving_bank_identifier_code'], 12, '');
            $recurringPlan->payment_provider_customer_id = $code.':'.$record['receiving_bank_account_number'];
            $recurringPlan->payment_provider = PaymentProvider::DBS_SINGAPORE;
            $recurringPlan->payment_provider_payment_method_id = PaymentMethodType::COLLECTION;

            $data = $recurringPlan->data;

            $data['dbs'] = [
                'dda' => $record,
            ];

            $recurringPlan->data = $data;
            $recurringPlan->status = RecurringPlanStatus::ACTIVE;
            $recurringPlan->save();

            // From the existing codes, it seems like we are not charging our customer until the recurring start date,
            // so at here we just update the status without pulling any fund from their account. This will be done
            // automatically via daily cron job when the recurring starts.

            $recurringPlan->notify(new NotifySubscriptionViaDdaActivated);
        }
    }
}
