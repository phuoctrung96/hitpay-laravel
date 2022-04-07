<?php


namespace App\Pipes\Export\Sales\HitpayToQuickbooks\Charge;


use App\Business\Charge;
use App\Services\Quickbooks\ManagerFactory;
use Closure;
use QuickBooksOnline\API\Data\IPPCustomer;

class QuickbooksCustomer
{
    public function handle(array $passable, Closure $next)
    {
        $passable['customer'] = $this->getQuickbooksCustomer($passable['charge']);

        return $next($passable);
    }

    private function getQuickbooksCustomer(Charge $charge): IPPCustomer
    {
        $manager = ManagerFactory::makeCustomerManager($charge->business->quickbooksIntegration);

        $name = $this->customerName($charge);
        $email = $this->customerEmail($charge);
        $currency = $charge->currency;

        return cache()->remember('qb-customer-' . $email, now()->addMinutes(1), function() use ($manager, $name, $email, $currency) {
            if(!$customer = $manager->findByEmail($email, $name)) {
                $customer = $manager->create($name, $email, $currency);
            }

            return $customer;
        });
    }

    private function customerName(Charge $charge): string
    {
        return !empty($charge->customer_name) ? $charge->customer_name : (!empty($charge->customer_email) ? $charge->customer_email : 'Default customer');
    }

    private function customerEmail(Charge $charge): string
    {
        return !empty($charge->customer_email) ? $charge->customer_email : 'default-customer@hit-pay.com';
    }
}
