<?php

namespace App\Http\Controllers\Api;

use App;
use App\Business;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;

class GatewayProviderStoreController extends Controller
{
    /**
     * @param Request $request
     * @param Business $business
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, Business $business): \Illuminate\Http\JsonResponse
    {
        $items = App\Actions\Business\GatewayProviders\RetrieveShopGatewayProvider::withBusiness($business)->process();

        return Response::json(['items' => $items]);
    }
}
