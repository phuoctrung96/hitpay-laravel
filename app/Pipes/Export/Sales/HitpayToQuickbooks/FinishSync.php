<?php


namespace App\Pipes\Export\Sales\HitpayToQuickbooks;


use App\DTO\Export\HitpaySalesExportDto;

class FinishSync
{
    public function handle(HitpaySalesExportDto $passable, \Closure $next)
    {
        $passable->business->quickbooksIntegration->last_sync_date = now();
        $passable->business->quickbooksIntegration->save();

        return $next($passable);
    }
}
