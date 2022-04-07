<?php


namespace App\Pipes\Export\Sales\HitpayToQuickbooks\Charge;


use App\Business\Charge;
use Closure;
use QuickBooksOnline\API\Data\IPPPaymentMethod;

class FindQuickbooksPaymentMethodForCharge
{
    public function handle(array $passable, Closure $next)
    {
        $passable['chargePaymentMethod'] = $this->getPaymentMethod($passable['paymentMethods'], $passable['charge']);

        return $next($passable);
    }

    private function getPaymentMethod(array $paymentMethods, Charge $charge): IPPPaymentMethod
    {
        $paymentType = $charge->getPaymentProviderCode() == 'cash' ? 'cash' : 'paynow';

        return $paymentMethods[$paymentType] ?? $paymentMethods['paynow'];
    }
}
