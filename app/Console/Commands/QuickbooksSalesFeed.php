<?php

namespace App\Console\Commands;

use App\Services\Quickbooks\HitpaySalesExportService;
use Illuminate\Console\Command;

class QuickbooksSalesFeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickbooks:sales-feed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(HitpaySalesExportService $exportService) : int
    {
        $exportService->export();

        return 0;
    }
}
