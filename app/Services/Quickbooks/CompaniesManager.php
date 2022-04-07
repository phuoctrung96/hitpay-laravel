<?php


namespace App\Services\Quickbooks;


use QuickBooksOnline\API\Data\IPPCompany;
use QuickBooksOnline\API\DataService\DataService;

class CompaniesManager
{
    private const ENTITY_NAME = 'company';

    private DataService $dataService;

    public function __construct(DataService $dataService)
    {
        $this->dataService = $dataService;
    }

    public function find($id): ?IPPCompany
    {
        return $this->dataService->findByID(static::ENTITY_NAME, $id);
    }
}
