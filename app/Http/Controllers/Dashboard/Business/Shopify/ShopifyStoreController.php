<?php

namespace App\Http\Controllers\Dashboard\Business\Shopify;

use App\Business;
use App\BusinessShopifyStore;
use App\Http\Controllers\Controller;
use App\Services\Shopify\ShopifyApp;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;

class ShopifyStoreController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * @param Business $business
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Business $business)
    {
        Gate::inspect('view', $business)->authorize();

        $paginator = $business->shopifyStores()->paginate(BusinessShopifyStore::PAGINATE_NUMBER);

        return Response::view('dashboard.business.shopify.stores.index', [
            'business' => $business,
            'paginator' => $paginator,
        ]);
    }

    /**
     * @param Business $business
     * @param BusinessShopifyStore $businessShopifyStore
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function destroy(Business $business, BusinessShopifyStore $businessShopifyStore)
    {
        Gate::inspect('update', $business)->authorize();

        $shopifyApp = new ShopifyApp($business);

        $shopifyApp->uninstallApp($businessShopifyStore);

        Session::flash('success_message', 'Successfully deleted');

        return redirect()->back();
    }
}
