<?php


namespace App\Pipes\Export\Sales\HitpayToQuickbooks\Charge;


use App\Business\Charge;
use App\Services\Quickbooks\ManagerFactory;
use Closure;
use QuickBooksOnline\API\Data\IPPAccount;
use QuickBooksOnline\API\Facades\Account;

class QuickbooksExpenseAccount
{
    public function handle(array $passable, Closure $next)
    {
        $passable['expenseAccount'] = cache()->remember('qb-expense-account-' . $passable['charge']->business->id, now()->addMinutes(1), function() use ($passable) {
            return $this->getExpenseAccount($passable['charge']);
        });
        $passable['feesAccount'] = cache()->remember('qb-fees-account-' . $passable['charge']->business->id, now()->addMinutes(1), function() use ($passable) {
            return $this->getFeesAccount($passable['charge']);
        });

        return $next($passable);
    }

    private function getExpenseAccount(Charge $charge): IPPAccount
    {
        $dataService = ManagerFactory::makeDataService($charge->business->quickbooksIntegration);
        $records = $dataService->Query("select * from Account Where AccountType = 'Expense' and Name = 'Hitpay fees'");

        if(!empty($records)) {
            return $records[0];
        }

        return $dataService->Add(Account::create([
            'Name' => 'Hitpay fees',
            'AccountType' => 'Expense',
        ]));
    }

    private function getFeesAccount(Charge $charge): IPPAccount
    {
        $dataService = ManagerFactory::makeDataService($charge->business->quickbooksIntegration);
        $records = $dataService->Query("select * from Account Where AccountType = 'Bank' and Name = 'Hitpay Fees Account'");

        if(!empty($records)) {
            return $records[0];
        }

        return $dataService->Add(Account::create([
            'Name' => 'Hitpay Fees Account',
            'AccountType' => 'Bank',
        ]));
    }
}
