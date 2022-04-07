<?php

namespace App\Logics\Business;

use App\Business;
use App\Business\GatewayProvider;
use Illuminate\Support\Facades\DB;

class GatewayProviderRepository
{
    public static function store(Business $business, GatewayProvider $gatewayProvider) : GatewayProvider
    {
        return DB::transaction(function () use ($business, $gatewayProvider) : GatewayProvider {
            $business->gatewayProviders()->save($gatewayProvider);
    
            $gatewayProvider = $gatewayProvider->refresh();
    
            return $gatewayProvider;
        }, 3);
    }
    
    public static function update(GatewayProvider $gatewayProvider) : GatewayProvider
    {
        $gatewayProvider = DB::transaction(function () use ($gatewayProvider) : GatewayProvider {
            $gatewayProvider->save();

            $gatewayProvider = $gatewayProvider->refresh();

            return $gatewayProvider;
        }, 3);

        return $gatewayProvider;
    }

    public static function delete(GatewayProvider $gatewayProvider) : ?bool
    {
        return DB::transaction(function () use ($gatewayProvider) : ?bool {
            return $gatewayProvider->delete();
        }, 3);
    }
}
