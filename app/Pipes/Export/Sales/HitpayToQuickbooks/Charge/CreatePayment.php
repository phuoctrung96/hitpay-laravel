<?php


namespace App\Pipes\Export\Sales\HitpayToQuickbooks\Charge;


use App\Business\Charge;
use App\Services\Quickbooks\ManagerFactory;
use Closure;
use Illuminate\Support\Facades\Log;
use QuickBooksOnline\API\Data\IPPAccount;
use QuickBooksOnline\API\Data\IPPCustomer;
use QuickBooksOnline\API\Data\IPPInvoice;
use QuickBooksOnline\API\Data\IPPPayment;
use QuickBooksOnline\API\Data\IPPPaymentMethod;
use QuickBooksOnline\API\Facades\Payment;

class CreatePayment
{
    public function handle(array $passable, Closure $next)
    {
        $passable['payment'] = $this->createInvoicePayment(
            $passable['charge'],
            $passable['chargePaymentMethod'],
            $passable['customer']
        );

        return $next($passable);
    }

    private function createInvoicePayment(Charge $charge, IPPPaymentMethod $paymentMethod, IPPCustomer $customer): IPPPayment
    {
        $dataService = ManagerFactory::makeDataService($charge->business->quickbooksIntegration);
        $payment = $dataService->Add($this->preparePayload(
            $paymentMethod,
            $customer,
            $charge->business->quickbooksIntegration->sales_account_id,
            $charge->amount / 100,
            $charge
        ));

        Log::channel('quickbooks-invoices')->info('Payment => ' . json_encode($payment, JSON_PRETTY_PRINT));

        return $payment;
    }

    private function preparePayload(
        IPPPaymentMethod $paymentMethod,
        IPPCustomer $customer,
        string $depositAccountId,
        float $amount,
        string $currency
    ): IPPPayment
    {
        $payload = Payment::create([
            'CustomerRef' => [
                'value' => $customer->Id,
            ],
            'CurrencyRef' => [
                'value' => strtoupper($currency)
            ],
            'PaymentMethodRef' => [
                'value' => $paymentMethod->Id,
            ],
            'TotalAmt' => $amount,
            'DepositToAccountRef' => [
                'value' => $depositAccountId
            ]
        ]);

        return $payload;
    }
}
