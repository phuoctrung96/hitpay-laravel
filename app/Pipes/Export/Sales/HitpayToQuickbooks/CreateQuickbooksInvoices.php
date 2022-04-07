<?php


namespace App\Pipes\Export\Sales\HitpayToQuickbooks;

use App\DTO\Export\HitpaySalesExportDto;
use App\Pipes\Export\Sales\HitpayToQuickbooks\Charge\CreateQuickbooksInvoice;
use App\Pipes\Export\Sales\HitpayToQuickbooks\Charge\FindQuickbooksPaymentMethodForCharge;
use App\Pipes\Export\Sales\HitpayToQuickbooks\Charge\CreatePurchase;
use App\Pipes\Export\Sales\HitpayToQuickbooks\Charge\CreatePayment;
use App\Pipes\Export\Sales\HitpayToQuickbooks\Charge\MarkChargeAsImported;
use App\Pipes\Export\Sales\HitpayToQuickbooks\Charge\QuickbooksCustomer;
use App\Pipes\Export\Sales\HitpayToQuickbooks\Charge\QuickbooksExpenseAccount;
use App\Pipes\Export\Sales\HitpayToQuickbooks\Charge\QuickbooksInventoryItem;
use App\Pipes\Export\Sales\HitpayToQuickbooks\Charge\QuickbooksPaymentMethods;
use Closure;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Log;

class CreateQuickbooksInvoices
{
    private Pipeline $pipeline;

    public function __construct(Pipeline $pipeline)
    {
        $this->pipeline = $pipeline;
    }

    public function handle(HitpaySalesExportDto $passable, Closure $next)
    {
        foreach ($passable->chargesCollection as $charge) {
            try {
                $result = $this->pipeline
                    ->send([
                        'charge' => $charge,
                    ])
                    ->through([
                        QuickbooksCustomer::class,
                        QuickbooksInventoryItem::class,
                        QuickbooksExpenseAccount::class,
                        QuickbooksPaymentMethods::class,
                        FindQuickbooksPaymentMethodForCharge::class,
                        CreatePayment::class,
                        CreateQuickbooksInvoice::class,
                        CreatePurchase::class,
                        MarkChargeAsImported::class,
                    ])
                    ->via('handle')
                    ->thenReturn();
            } catch (\Exception $exception) {
                Log::channel('quickbooks-errors')->error($exception, ['charge' => $charge]);
                dump($exception);
            }
        }

        return $next($passable);
    }
}
