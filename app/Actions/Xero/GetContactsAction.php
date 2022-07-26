<?php

namespace App\Actions\Xero;

use App\Business;
use App\Services\XeroApiFactory;
use XeroAPI\XeroPHP\Models\Accounting\Contacts;

class GetContactsAction
{
    private \XeroAPI\XeroPHP\Api\AccountingApi $accountingApi;
    private string $tenantId;

    public function __construct(Business $business)
    {
        $this->accountingApi = XeroApiFactory::makeAccountingApi($business);
        $this->tenantId = $business->xero_tenant_id;
    }

    public function __invoke(): Contacts
    {
        return $this->accountingApi->getContacts($this->tenantId);
    }
}
