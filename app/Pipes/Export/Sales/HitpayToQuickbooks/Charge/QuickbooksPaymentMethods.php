<?php


namespace App\Pipes\Export\Sales\HitpayToQuickbooks\Charge;


use App\Business\Charge;
use App\Services\Quickbooks\ManagerFactory;
use Closure;
use Illuminate\Support\Str;
use QuickBooksOnline\API\Data\IPPPaymentMethod;

class QuickbooksPaymentMethods
{
    public function handle(array $passable, Closure $next)
    {
        $passable['paymentMethods'] = $this->getPaymentMethods($passable['charge']);

        return $next($passable);
    }

    private function getPaymentMethods(Charge $charge): array
    {
        $manager = ManagerFactory::makePaymentMethodManager($charge->business->quickbooksIntegration);
        return cache()->remember('qb-payment-methods-' . $charge->business_id, now()->addMinutes(1), function() use($manager, $charge) {
            $methods = [];
            foreach ($manager->all() as $method) {
                $methods[Str::camel($method->Name)] = $method;
            }

            if(!isset($methods['paynow'])) {
                $methods['paynow'] = $this->createPaynowPaymentMethod($charge);
            }
            return $methods;
        });
    }

    private function createPaynowPaymentMethod(Charge $charge): IPPPaymentMethod
    {
        $manager = ManagerFactory::makePaymentMethodManager($charge->business->quickbooksIntegration);
        $payload = new IPPPaymentMethod();
        $payload->Name = 'Paynow';
        return $manager->add($payload);
    }
}
