<?php


namespace App\Services\Quickbooks;


use QuickBooksOnline\API\DataService\DataService;

class AccountsManager
{
    private const ENTITY_NAME = 'account';

    private DataService $dataService;

    public function __construct(DataService $dataService)
    {
        $this->dataService = $dataService;
    }

    public function get(): ?array
    {
        return $this->dataService->FindAll(static::ENTITY_NAME);
    }
}
