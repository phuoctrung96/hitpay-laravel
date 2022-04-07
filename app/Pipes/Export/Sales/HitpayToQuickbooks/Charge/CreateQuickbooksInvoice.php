<?php


namespace App\Pipes\Export\Sales\HitpayToQuickbooks\Charge;


use App\Business\Charge;
use App\Models\QuickbooksLog;
use App\Services\Quickbooks\ManagerFactory;
use Closure;
use Illuminate\Support\Facades\Log;
use QuickBooksOnline\API\Data\IPPCustomer;
use QuickBooksOnline\API\Data\IPPInvoice;
use QuickBooksOnline\API\Data\IPPItem;
use QuickBooksOnline\API\Data\IPPPayment;
use QuickBooksOnline\API\Facades\Invoice;

class CreateQuickbooksInvoice
{
    public function handle(array $passable, Closure $next)
    {
        $payload = $this->chargeToPayload($passable['charge'], $passable['customer'], $passable['inventoryItem'], $passable['payment']);
        $passable['invoice'] = $this->createInvoice($payload, $passable['charge']);

        return $next($passable);
    }

    private function createInvoice(IPPInvoice $payload, Charge $charge): IPPInvoice
    {
        $dataService = ManagerFactory::makeDataService($charge->business->quickbooksIntegration);
        $invoice = $dataService->Add($payload);

        Log::channel('quickbooks-invoices')->info('Invoice => '. json_encode($invoice, JSON_PRETTY_PRINT));
        QuickbooksLog::create([
            'business_charge_id' => $charge->id,
            'is_fee' => false,
            'quickbooks_invoice_id' => $invoice->Id,
            'payload' => json_encode($payload),
            'quickbooks_invoice' => json_encode($invoice),
        ]);

        return $invoice;
    }

    private function chargeToPayload(Charge $charge, IPPCustomer $customer, IPPItem $item, IPPPayment $payment): IPPInvoice
    {
        return Invoice::create([
            "Balance" => $charge->amount / 100,
            "Line" => [
                [
                    "Description" => $this->getChargeDescription($charge),
                    "Amount" => $charge->amount / 100,
                    "DetailType" => "SalesItemLineDetail",
                    "SalesItemLineDetail" => [
                        "ItemRef" => [
                            "value" => $item->Id,
                            "name" => "Hitpay Services"
                        ]
                    ]
                ]
            ],
            'LinkedTxn' => [
                [
                    'TxnId' => $payment->Id,
                    'TxnType' => 'Payment'
                ]
            ],
            "CustomerRef" => [
                "value" => $customer->Id,
                "name" => $customer->DisplayName,
            ],
            "CurrencyRef" => [
                "value" => strtoupper($charge->currency)
            ]
        ]);
    }

    private function getChargeDescription(Charge $charge): string
    {
        if (in_array($charge->payment_provider_charge_method, Charge::stripe_methods)) {
            $description = config('app.name') . '-' . $charge->business->getName() . ' - Stripe Payment Method(' . $charge->id . ')';
        } elseif ($charge->payment_provider_charge_method == 'paynow_online') {
            $description = config('app.name') . '-' . $charge->business->getName() . ' - PayNow Online Payment Method(' . $charge->id . ')';
        } else {
            $description = config('app.name') . '-' . $charge->business->getName() . ' - Cash Payment Method(' . $charge->id . ')';
        }

        return $description;
    }
}
