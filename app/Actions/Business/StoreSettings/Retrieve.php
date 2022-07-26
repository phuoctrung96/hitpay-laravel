<?php

namespace App\Actions\Business\StoreSettings;

use App\Business;

class Retrieve extends Action
{
    /**
     * @return Business\BusinessShopSettings|\Illuminate\Database\Eloquent\Model|null
     * @throws \Exception
     */
    public function process()
    {
        if (! $this->business instanceof Business) {
            throw new \Exception("Business not set!");
        }

        $storeSettings = $this->business->shopSettings()->first();

        if (! $storeSettings instanceof Business\BusinessShopSettings) {
            return null;
        }

        return $storeSettings;
    }
}
