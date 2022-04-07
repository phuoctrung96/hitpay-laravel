<?php


namespace App\Pipes\Export\Sales\HitpayToQuickbooks;


use App\DTO\Export\HitpaySalesExportDto;
use Carbon\Carbon;
use Closure;

class CalculateSyncDates
{
    public function handle(HitpaySalesExportDto $passable, Closure $next)
    {
        $lastSyncDate = !empty($passable->business->quickbooksIntegration->last_sync_date)
            ? $passable->business->quickbooksIntegration->last_sync_date
            : $passable->business->quickbooksIntegration->initial_sync_date;

        $passable->startDate = $lastSyncDate->isPast()
            ? $lastSyncDate
            : Carbon::yesterday();

        return $next($passable);
    }
}
