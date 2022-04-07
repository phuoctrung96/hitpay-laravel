<?php


namespace App\Services\Quickbooks;


use QuickBooksOnline\API\Data\IPPCustomer;
use QuickBooksOnline\API\Data\IPPPaymentMethod;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Facades\Customer;

class PaymentMethodsManager
{
    private DataService $dataService;

    public function __construct(DataService $dataService)
    {
        $this->dataService = $dataService;
    }

    public function all(): array
    {
        return $this->dataService->Query("select * from PaymentMethod");
    }

    public function add(IPPPaymentMethod $payload): IPPPaymentMethod
    {
        return $this->dataService->Add($payload);
    }
}
