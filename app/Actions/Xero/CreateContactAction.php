<?php

namespace App\Actions\Xero;

use App\Business;
use App\Services\XeroApiFactory;
use XeroAPI\XeroPHP\Models\Accounting\Contact;
use XeroAPI\XeroPHP\Models\Accounting\Contacts;

class CreateContactAction
{
    private \XeroAPI\XeroPHP\Api\AccountingApi $accountingApi;
    private string $tenantId;

    public function __construct(Business $business)
    {
        $this->accountingApi = XeroApiFactory::makeAccountingApi($business);
        $this->tenantId = $business->xero_tenant_id;
    }

    public function __invoke(string $name, ?string $email = null): Contact
    {
        $contact = new Contact();

        $contact->setName($name);

        if(!empty($email)) {
            $contact->setEmailAddress($email);
        }

        $contacts = new Contacts();
        $contacts->setContacts([$contact]);

        $apiResponse = $this->accountingApi->createContacts($this->tenantId, $contacts);

        return $apiResponse->getContacts()[0];
    }
}
