<?php


namespace App\Services\Quickbooks;


use App\Business;
use App\DTO\Export\HitpaySalesExportDto;
use App\Pipes\Export\Sales\HitpayToQuickbooks\CalculateSyncDates;
use App\Pipes\Export\Sales\HitpayToQuickbooks\CollectCharges;
use App\Pipes\Export\Sales\HitpayToQuickbooks\ConvertHitpayChargesToQuickbooksInvoices;
use App\Pipes\Export\Sales\HitpayToQuickbooks\CreateQuickbooksInvoices;
use App\Pipes\Export\Sales\HitpayToQuickbooks\FinishSync;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Spatie\DataTransferObject\DataTransferObject;

class HitpaySalesExportService
{

    /**
     * @var Pipeline
     */
    private $pipeline;

    public function __construct(Pipeline $pipeline)
    {
        $this->pipeline = $pipeline;
    }

    public function export(): void
    {
        foreach ($this->businesses() as $business) {
            $this->syncBusinessSales($business);
        }
    }

    public function syncBusinessSales(Business $business): void
    {
        try {
            $dataTransferObject = new HitpaySalesExportDto();
            $dataTransferObject->business = $business;

            $this->pipeline
                ->send($dataTransferObject)
                ->through([
                    CalculateSyncDates::class,
                    CollectCharges::class,
                    CreateQuickbooksInvoices::class,
                    FinishSync::class,
                ])
                ->via('handle')
                ->thenReturn();
        } catch (\Exception $exception) {
            Log::channel('quickbooks-errors')->error($exception, [
                'business' => $business,
                'trace' => $exception->getTrace()
            ]);
        }
    }

    private function businesses(): Collection
    {
        return Business::query()
            ->with('quickbooksIntegration')
            ->whereHas('quickbooksIntegration', function($query) {
                return $query
                    ->whereNotNull('initial_sync_date')
                    ->whereDate('initial_sync_date', '<', now());
            })
            ->get();
    }
}
