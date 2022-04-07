<?php


namespace App\Services\Quickbooks;


use QuickBooksOnline\API\Data\IPPCustomer;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Facades\Customer;

class CustomersManager
{
    private const ENTITY_NAME = 'customer';

    private DataService $dataService;

    public function __construct(DataService $dataService)
    {
        $this->dataService = $dataService;
    }

    public function findByEmail(string $email, string $name): ?IPPCustomer
    {
        try {
            $result = $this->dataService->Query("select * from Customer Where DisplayName = '{$name}'");
            if(!empty($result)) {
                return $result[0];
            }

            return null;
        } catch (\Exception $exception) {
            dd($exception);
        }
    }

    public function create(string $name, string $email, string $currency): IPPCustomer
    {
        try {
            $customer = Customer::create([
                'DisplayName' => $name,
                'PrimaryEmailAddr' => [
                    'Address' => $email
                ],
                'CurrencyRef' => [
                    'value' => $currency
                ]
            ]);
            return $this->dataService->Add($customer);
        } catch (\Exception $exception) {
            dd($exception);
        }
    }
}
