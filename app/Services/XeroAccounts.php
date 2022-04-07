<?php


namespace App\Services;


use App\Business;
use Illuminate\Support\Collection;
use XeroAPI\XeroPHP\Models\Accounting\Account;
use XeroAPI\XeroPHP\Models\Accounting\Accounts;

class XeroAccounts
{

    private static $accounts = [];

    public function getAccounts(Business $business): array
    {
        $accounts = [];

        /** @var Account $xeroAccount */
        foreach($this->getAllAccounts($business) as $xeroAccount) {
            $accounts[] = [
                'id' => $xeroAccount->getAccountId(),
                'name' => $xeroAccount->getName() . ' ' . $xeroAccount->getCode(),
                'type' => $xeroAccount->getType(),
                'can_accept_payments' => $xeroAccount->getEnablePaymentsToAccount(),
            ];
        }

        return $accounts;
    }

    public function getFeeAccounts(Business $business): Collection
    {
        $accounts = [];

        /** @var Account $xeroAccount */
        foreach($this->getAllAccounts($business) as $xeroAccount) {
            if($xeroAccount->getType() == 'EXPENSE') {
                $accounts[] = [
                    'id' => $xeroAccount->getAccountId(),
                    'name' => $xeroAccount->getName() . ' ' . $xeroAccount->getCode(),
                    'type' => $xeroAccount->getType(),
                    'can_accept_payments' => $xeroAccount->getEnablePaymentsToAccount(),
                ];
            }
        }

        return collect($accounts);
    }

    public function getBankAccounts(Business $business): Collection
    {
        $accounts = [];

        /** @var Account $xeroAccount */
        foreach($this->getAllAccounts($business) as $xeroAccount) {
            if($xeroAccount->getType() == 'BANK') {
                $accounts[] = [
                    'id' => $xeroAccount->getAccountId(),
                    'name' => $xeroAccount->getName() . ' ' . $xeroAccount->getCode(),
                    'type' => $xeroAccount->getType(),
                    'can_accept_payments' => $xeroAccount->getEnablePaymentsToAccount(),
                ];
            }
        }

        return collect($accounts);
    }

    public function getAllAccounts(Business $business)
    {
        if(empty($business->xero_refresh_token)) {
            return  [];
        }

        $api = XeroApiFactory::makeAccountingApi($business);

        if(empty(static::$accounts)) {
            /** @var Accounts $xeroAccounts */
            static::$accounts = $api->getAccounts($business->xero_tenant_id)->getAccounts();
        }

        return static::$accounts;
    }
}
