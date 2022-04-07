<?php


namespace App\Pipes\Export\Sales\HitpayToQuickbooks\Charge;


use App\Business\Charge;
use App\Services\Quickbooks\ManagerFactory;
use Closure;
use Illuminate\Support\Facades\Log;
use QuickBooksOnline\API\Data\IPPAccount;
use QuickBooksOnline\API\Data\IPPPaymentMethod;
use QuickBooksOnline\API\Data\IPPPurchase;
use QuickBooksOnline\API\Data\IPPVendor;
use QuickBooksOnline\API\Facades\Account;
use QuickBooksOnline\API\Facades\Purchase;
use QuickBooksOnline\API\Facades\Vendor;

class CreatePurchase
{
    public function handle(array $passable, Closure $next)
    {
        if($passable['charge']->getTotalFee() > 0) {
            $passable['purchase'] = $this->createPurchase(
                $passable['charge'],
                $passable['chargePaymentMethod'],
                $passable['expenseAccount'],
                $passable['feesAccount']
            );
        }

        return $next($passable);
    }

    private function createPurchase(Charge $charge, IPPPaymentMethod $paymentMethod, IPPAccount $expenseAccount, IPPAccount $feesAccount): IPPPurchase
    {
        $dataService = ManagerFactory::makeDataService($charge->business->quickbooksIntegration);
        $purchase = $dataService->Add($this->createPayload($charge, $paymentMethod, $expenseAccount, $feesAccount));

        Log::channel('quickbooks-invoices')->info('Purchase => ' . json_encode($purchase, JSON_PRETTY_PRINT));

        return $purchase;
    }

    private function createPayload(Charge $charge, IPPPaymentMethod $paymentMethod, IPPAccount $expenseAccount, IPPAccount $feesAccount): IPPPurchase
    {
        $payload = Purchase::create([
            'PaymentType' => $paymentMethod->Name == 'Cash' ? $paymentMethod->Name : 'CreditCard',
            'PaymentMethodRef' => [
                'value' => $paymentMethod->Id,
            ],
            'AccountRef' => [
                'value' => $feesAccount->Id,
            ],
            'Line' => [
                [
                    'DetailType' => 'AccountBasedExpenseLineDetail',
                    'Amount' => $charge->getTotalFee() / 100,
                    'AccountBasedExpenseLineDetail' => [
                        'AccountRef' => [
                            'Value' => $expenseAccount->Id,
                        ]
                    ],
                    'Description' => 'Hitpay Services Fee - #' . $charge->id,
                ]
            ],
            'PrivateNote' => 'Hitpay Services Fee',
            'CurrencyRef' => [
                'value' => $charge->currency
            ],
            'EntityRef' => [
                'value' => $this->getVendor($charge)->Id,
                'name' => 'Hitpay',
            ],
        ]);

        return $payload;
    }

    private function getVendor(Charge $charge): IPPVendor
    {
        return cache()->remember('qb-vendor-' . $charge->business->id, now()->addMinutes(1), function () use($charge) {
            $dataService = ManagerFactory::makeDataService($charge->business->quickbooksIntegration);
            $vendors = $dataService->Query("select * from Vendor Where DisplayName = 'Hitpay'");

            if(empty($vendors)) {
                $vendor = $dataService->Add(Vendor::create([
                    'DisplayName' => 'Hitpay',
                    'CurrencyRef' => [
                        'value' => 'SGD'
                    ]
                ]));
            } else {
                $vendor = $vendors[0];
            }

            return $vendor;
        });
    }
}
