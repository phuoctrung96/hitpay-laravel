<?php

namespace App\Console\Commands;

use App\IPv4Geolocation;
use App\IPv6Geolocation;
use HitPay\Agent\Contracts\Geolocation;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use League\Csv\Reader;
use League\Csv\Statement;

class UpdateGeolocation extends Command
{

    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hitpay:update-geolocation
                {--ipv4 : Update the IPv4 geolocation table}
                {--ipv6 : Update the IPv6 geolocation table}
                {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the IP geolocation tables';

    /**
     * Execute the console command.
     */
    public function handle() : void
    {
        if (!$this->confirmToProceed()) {
            return;
        }

        // TODO - 2019-12-11
        // We are using the "IP-COUNTRY-REGION-CITY-LATITUDE-LONGITUDE" (DB5) databases provided by IP2Location. These
        // databases can be downloaded from their website (https://lite.ip2location.com). Ideally, this command should
        // download the latest version of the database using IP2LOCATION APIs, extract it and update to our database,
        // instead of required the files to be manually uploaded to server before this command is run.
        //
        // KIV: Further studies required. Commercial license might be required.

        if (!$this->option('ipv4') && !$this->option('ipv6')) {
            $this->call('hitpay:update-geolocation', [
                '--ipv4' => true,
                '--ipv6' => true,
            ]);

            return;
        }

        // TODO - 2019-12-11
        // This is a temporary solution to solve memory exhausted issue when Flare is collecting queries for debugging.
        // This issue will happen only when Ignition and its query recorder are registered and enabled. Anyway, this
        // shouldn't happen in production environment, because Ignition is a require-dev package and it shouldn't be
        // installed.
        //
        // SEGMENT - START

        $queryRecorderClass = 'Facade\Ignition\QueryRecorder\QueryRecorder';

        if ($this->getLaravel()->bound($queryRecorderClass) && Config::get('flare.reporting.report_queries')) {
            Event::forget(QueryExecuted::class);
        }

        // SEGMENT - END

        $this->output->newLine();

        if ($this->option('ipv4')) {
            $this->updateTable(IPv4Geolocation::newModelInstance(), 'IP2LOCATION-LITE-DB5.CSV');
        }

        if ($this->option('ipv6')) {
            if ($this->option('ipv4')) {
                $this->output->newLine();
            }

            $this->updateTable(IPv6Geolocation::newModelInstance(), 'IP2LOCATION-LITE-DB5.IPV6.CSV');
        }

        $this->output->newLine();
        $this->comment('++++++++++++++++++    <info>DONE</info>    +++++++++++++++++++');
        $this->output->newLine();
    }

    /**
     * Update related table.
     *
     * @param \HitPay\Agent\Contracts\Geolocation $geolocation
     * @param string $filename
     */
    public function updateTable(Geolocation $geolocation, string $filename) : void
    {
        $reader = Reader::createFromPath(storage_path('app'.DIRECTORY_SEPARATOR.$filename));
        $geolocationRows = (new Statement)->process($reader);
        $connection = $geolocation->getConnectionName();
        $tableName = $geolocation->getTable();
        $tempTableName = 'temp_'.$tableName;
        $count = 0;
        $header = get_class($geolocation).' Table';
        $length = Str::length(strip_tags($header)) + 24;

        $this->comment(str_repeat('+', $length));
        $this->comment('+           <info>'.$header.'</info>           +');
        $this->comment(str_repeat('+', $length));
        $this->output->newLine();

        DB::connection($connection)->table($tempTableName)->truncate();

        $this->line('  '.Date::now()->format('h:i:s a').' - Rows inserted: '
            .str_pad(number_format($count), 16, ' ', STR_PAD_LEFT));

        foreach ($geolocationRows as $row) {
            $attributes = [
                'ip_from' => $row[0],
                'ip_to' => $row[1],
                'country_code' => null,
                'country_name' => null,
                'region_name' => null,
                'city_name' => null,
                'latitude' => null,
                'longitude' => null,
            ];

            if ($row[2] !== '-') {
                $attributes['country_code'] = $row[2];
                $attributes['country_name'] = $row[3];

                if ($row[4] !== '-') {
                    $attributes['region_name'] = $row[4];

                    if ($row[5] !== '-') {
                        $attributes['city_name'] = $row[5];
                    }
                }

                $attributes['latitude'] = $row[6];
                $attributes['longitude'] = $row[7];
            }

            $data[] = $attributes;

            unset($attributes);

            $count++;

            if ($count % 1000 === 0) {
                DB::connection($connection)->table($tempTableName)->insert($data);

                unset($data);

                switch (true) {

                    case $count % 155000 === 0:
                    case $count === 75000:
                    case $count === 35000:
                    case $count === 10000:
                    case $count === 5000:
                        $this->line('  '.Date::now()->format('h:i:s a').' - Rows inserted: '
                            .str_pad(number_format($count), 16, ' ', STR_PAD_LEFT));

                        break;
                }
            }
        }

        if (isset($data)) {
            DB::connection($connection)->table($tempTableName)->insert($data);
        }

        $this->output->newLine();
        $this->line('  Total rows inserted: '.str_pad(number_format($count), 24, ' ', STR_PAD_LEFT));

        $bridgeTableName = $tempTableName.'_temp';

        Schema::connection($connection)->rename($tableName, $bridgeTableName);
        Schema::connection($connection)->rename($tempTableName, $tableName);
        Schema::connection($connection)->rename($bridgeTableName, $tempTableName);
    }
}
