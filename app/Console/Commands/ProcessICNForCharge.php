<?php

namespace App\Console\Commands;

use App\Actions\Business\DBS\ICN\ForCharge;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use Symfony\Component\Console\Input\InputArgument;

class ProcessICNForCharge extends Command
{
    protected int $limit = 50;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'hitpay:sync-icn-to-charge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync DBS ICN to charge';

    /**
     * Execute the console command.
     */
    public function handle() : int
    {
        $filename = $this->argument('filename');

        if (!is_string($filename)) {
            $this->error('The filename is invalid.');

            return 1;
        }

        $filepath = "dbs-icn-files/{$filename}.csv";

        if (!Storage::disk('local')->exists($filepath)) {
            $this->error("The file doesn't exist. Please make sure the filepath is '{$filepath}'.");

            return 1;
        }

        $file = Storage::disk('local')->get($filepath);

        $reader = Reader::createFromString($file);

        $records = Collection::make();

        foreach ($reader->getRecords() as $line => $json) {
            if (!isset($json[0])) {
                $invalid[] = "The line {$line} is invalid.";

                continue;
            }

            $data = json_decode($json[0], true);

            if (!is_array($data) || !$this->isValid($data)) {
                $this->error("The JSON payload in line {$line} is invalid.");

                return 1;
            }

            $records->push($data);
        }

        if ($records->count() > $this->limit) {
            $this->error("Please do not include more than {$this->limit} records at once.");

            return 1;
        }

        $records = $records->unique('txnInfo.txnRefId');

        foreach ($records as $record) {
            $reference = $filename = $record['txnInfo']['customerReference'];

            $now = Date::now();

            $filename = "paynow/{$now->toDateString()}/{$filename}-via-cli-{$now->timestamp}.{$now->micro}.txt";

            ForCharge::withReference($reference)->filepath($filename)->data($record)->process();
        }

        return 0;
    }

    private function isValid(array $data) : bool
    {
        switch (false) {
            case isset($data['header']['msgId']) && is_string($data['header']['msgId']):
            case isset($data['header']['orgId']) && is_string($data['header']['orgId']):
            case isset($data['header']['timeStamp']) && is_string($data['header']['timeStamp']):
            case isset($data['header']['ctry']) && $data['header']['ctry'] === 'SG':
            case isset($data['txnInfo']['txnType']) && is_string($data['txnInfo']['txnType']):
            case isset($data['txnInfo']['customerReference']) && is_string($data['txnInfo']['customerReference']):
            case isset($data['txnInfo']['txnRefId']) && is_string($data['txnInfo']['txnRefId']):
            case isset($data['txnInfo']['txnDate']) && is_string($data['txnInfo']['txnDate']):
            case isset($data['txnInfo']['valueDt']) && is_string($data['txnInfo']['valueDt']):
            case isset($data['txnInfo']['receivingParty']['name']) && is_string($data['txnInfo']['receivingParty']['name']):
            case isset($data['txnInfo']['receivingParty']['accountNo']) && is_string($data['txnInfo']['receivingParty']['accountNo']):
            case isset($data['txnInfo']['amtDtls']['txnCcy']) && $data['txnInfo']['amtDtls']['txnCcy'] === 'SGD':
            case isset($data['txnInfo']['amtDtls']['txnAmt']) && is_numeric($data['txnInfo']['amtDtls']['txnAmt']):
            case isset($data['txnInfo']['senderParty']['name']) && is_string($data['txnInfo']['senderParty']['name']):
            case isset($data['txnInfo']['senderParty']['senderBankId']) && is_string($data['txnInfo']['senderParty']['senderBankId']):
                return false;
        }

        return true;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            [ 'filename', InputArgument::REQUIRED, 'The filename of the JSON data from DBS, in CSV.' ],
        ];
    }
}
