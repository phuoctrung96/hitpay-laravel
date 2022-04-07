<?php


namespace App\Pipes\Export\Sales\HitpayToQuickbooks\Charge;


use Closure;

class MarkChargeAsImported
{
    public function handle(array $passable, Closure $next)
    {
        $passable['charge']->update([
            'quickbooks_imported' => true,
        ]);

        return $next($passable);
    }
}
