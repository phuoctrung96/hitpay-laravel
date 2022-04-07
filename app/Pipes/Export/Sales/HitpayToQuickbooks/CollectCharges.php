<?php


namespace App\Pipes\Export\Sales\HitpayToQuickbooks;


use App\DTO\Export\HitpaySalesExportDto;
use App\Enumerations\Business\ChargeStatus;

class CollectCharges
{
    public function handle(HitpaySalesExportDto $passable, \Closure $next)
    {
        $query = $passable->business->charges()->with('target')
            ->whereNull('quickbooks_imported')
            ->when($passable->startDate->isPast(), function($query) use ($passable) {
                return $query
                    ->whereDate('closed_at', '>=', $passable->startDate->toDateString())
                    ->whereDate('closed_at', '<=', date('Y-m-d'));
            })
            ->when(!$passable->startDate->isPast(), function ($query) {
                return $query->whereDate('closed_at', '=', date('Y-m-d', strtotime('-1 day')));
            })
            ->where('status', ChargeStatus::SUCCEEDED);

        $passable->chargesCollection = $query->get();

        return $next($passable);
    }
}
