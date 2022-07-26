<?php

namespace App\Http\Controllers\Shop;

use App\Actions\Business\GatewayProviders\RetrieveShopGatewayProvider;
use App\Business;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades;

class ShopGatewayProviderController extends Controller
{
    /**
     * @param Request $request
     * @param Business $business
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function __invoke(Request $request, Business $business): \Illuminate\Http\JsonResponse
    {
        $items = RetrieveShopGatewayProvider::withBusiness($business)->process();

        return Facades\Response::json(['items' => $items]);
    }
}
