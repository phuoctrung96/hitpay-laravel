<?php


namespace App\Http\Controllers;


use App\Business\Charge;
use App\Business\PaymentRequest;
use App\Http\Requests\XeroInvoiceRequest;
use App\Manager\ChargeManagerInterface;
use App\Services\XeroCheckout;
use App\Services\XeroSalesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class XeroCheckoutController extends Controller
{
    public function show(XeroInvoiceRequest $request, XeroCheckout $xeroCheckout)
    {
        $paymentRequest = $xeroCheckout->createPaymentRequest($request);

        return redirect($paymentRequest['url']);
    }

    public function confirm(Request $request, XeroCheckout $xeroCheckout)
    {
        /** @var PaymentRequest $paymentRequest */
        $paymentRequest = PaymentRequest::findOrFail($request->input('payment_request_id'));
        $this->validateRequest($request, $paymentRequest);

        sleep(3);

        $maxAttempts = 5;
        $attempt = 1;
        $isInvoiceMarkedAsPaid = false;
        while($attempt <= $maxAttempts && !$isInvoiceMarkedAsPaid) {
            $payments = $paymentRequest->getPayments();
            if($payments->count() > 0) {
                /** @var Charge $payment */
                $payment = $payments->shift();
                $xeroSalesService = new XeroSalesService($paymentRequest->business);
                $isInvoiceMarkedAsPaid = $xeroSalesService->markInvoiceAsPaid(
                    $request->input('invoice'),
                    $payment->getFormattedFees(),
                    $payment->closed_at
                );
            }
            $attempt++;
            sleep(2);
        }
    }

    /**
     * Check the request returned from Shopify.
     *
     * @param Request $request
     * @param PaymentRequest $charge
     */
    private function validateRequest(Request $request, PaymentRequest $charge): void
    {
        if (!$request->has('hmac')) {
            App::abort(404);
        }

        if(!$request->has('invoice')) {
            App::abort(404);
        }

        if($request->input('status') != 'completed') {
            App::abort(404);
        }

        $isValidHmac = false;
        foreach ($charge->business->apiKeys()->where('is_enabled', 1)->get() as $apiKey) {
            if(hash_equals($request->input('hmac'), $this->makeHmacFromRequest($request, (string) $apiKey->salt))) {
                $isValidHmac = true;
            }
        }

        if(!$isValidHmac) {
            App::abort(404);
        }
    }

    private function makeHmacFromRequest(Request $request, string $salt): string
    {
        return resolve(ChargeManagerInterface::class)
            ->generateSignatureArray($salt, $request->only(
                'payment_id',
                'payment_request_id',
                'phone',
                'amount',
                'currency',
                'status',
                'reference_number'
            ));
    }
}
