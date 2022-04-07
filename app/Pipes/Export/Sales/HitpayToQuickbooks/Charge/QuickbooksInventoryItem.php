<?php


namespace App\Pipes\Export\Sales\HitpayToQuickbooks\Charge;


use App\Business\Charge;
use App\Services\Quickbooks\ManagerFactory;
use Closure;
use QuickBooksOnline\API\Data\IPPItem;
use QuickBooksOnline\API\Facades\Item;

class QuickbooksInventoryItem
{
    public function handle(array $passable, Closure $next)
    {
        $passable['inventoryItem'] = cache()->remember('qb-inventory-' . $passable['charge']->business->id, now()->addMinutes(1), function() use ($passable) {
            return $this->getQuickbooksInventoryItem($passable['charge']);
        });

        return $next($passable);
    }

    private function getQuickbooksInventoryItem(Charge $charge): IPPItem
    {
        $dataService = ManagerFactory::makeDataService($charge->business->quickbooksIntegration);
        $inventories = $dataService->Query("select * from Item Where Name = 'Hitpay Services'");
        if(!empty($inventories)) {
            $item = $inventories[0];
        } else {
            $servicesAccounts = $dataService->Query("select * from Account Where AccountType = 'Income'");
            foreach ($servicesAccounts as $servicesAccountObject) {
                if($servicesAccountObject->Name == 'Services') {
                    $servicesAccount = $servicesAccountObject;
                }
            }
            if(empty($servicesAccount)) {
                $servicesAccount = $servicesAccounts[0];
            }
            $item = $dataService->Add(Item::create([
                'Name' => 'Hitpay Services',
                'Type' => 'Service',
                'IncomeAccountRef' => [
                    'value' => $servicesAccount->Id
                ]
            ]));
        }

        return $item;
    }
}
