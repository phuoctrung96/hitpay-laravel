<?php

namespace App\Manager;

use App\Business\GatewayProvider;
use App\Business;
use App\Logics\Business\GatewayProviderRepository;

class GatewayProviderManager
{
    public static function createNew()
    {
        return new GatewayProvider();
    }

    public static function create(Business $business, array $data) : GatewayProvider
    {
        $gatewayProvider            = self::createNew();
        $gatewayProvider->name      = $data['name'];
        $gatewayProvider->methods   = isset($data['methods']) && is_array($data['methods'])? json_encode($data['methods']): [];
        $gatewayProvider            = GatewayProviderRepository::store($business, $gatewayProvider);

        return $gatewayProvider;
    }

    public static function update(GatewayProvider $gatewayProvider, array $data) : GatewayProvider
    {
        $gatewayProvider->name      = $data['name'];
        $gatewayProvider->methods   = isset($data['methods']) && is_array($data['methods'])? json_encode($data['methods']): [];

        return GatewayProviderRepository::update($gatewayProvider);
    }

    public static function delete(GatewayProvider $gatewayProvider) : ?bool
    {
        return GatewayProviderRepository::delete($gatewayProvider);
    }
}
