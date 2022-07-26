<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\Business\GatewayProvider;
use App\Http\Controllers\Controller;
use App\Manager\GatewayProviderManager;
use App\Manager\BusinessManagerInterface;
use App\Enumerations\Business\PluginProvider;
use App\Enumerations\Business\PaymentMethodType;
use App\Http\Requests\GatewayProviderRequest;
use App\Services\XeroPaymentService;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\Response as AccessResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;

class GatewayProviderController extends Controller
{
    /**
     * ApiKeyController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(Business $business)
    {
        if (!Gate::allows('view-checkout', $business)) {
          return Response::view('dashboard.business.gateway-provider.no-providers', compact('business'));
        }

        $paginator = $business->gatewayProviders()->paginate();
        $names = PluginProvider::getAll(true);

        return Response::view('dashboard.business.gateway-provider.index', compact('business', 'paginator', 'names'));
    }

    /**
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \ReflectionException
     */
    public function create(Request $request, Business $business, BusinessManagerInterface $businessManager)
    {
        Gate::inspect('update', $business)->authorize();

        $data               = $this->formData($business, $businessManager);
        $gatewayProvider    = GatewayProviderManager::createNew();

        $gatewayProvider->methods = ['paynow_online', 'card', 'alipay', 'wechat'];

        return Response::view('dashboard.business.gateway-provider.form', compact('business', 'gatewayProvider', 'data'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param GatewayProviderRequest $request
     * @param \App\Business $business
     *
     * @param XeroPaymentService $paymentService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(GatewayProviderRequest $request, Business $business, XeroPaymentService $paymentService)
    {
        if (!Gate::allows('view-checkout', $business)) {
            Response::deny('You must setup your payment providers.');
        }

        if($request->input('name') == 'xero' && empty($business->xero_branding_theme)) {
            $url = route('dashboard.business.integration.xero.home', $business->getKey());
            Session::flash('success_message', 'You should <a href="'.$url.'">update xero settings</a> before adding payment gateway integration');

            return back();
        }

        try {
            $gatewayProvider = GatewayProviderManager::create($business, $request->all());

            if($request->input('name') == 'xero' && empty(!$business->xero_branding_theme)) {
                $paymentService->createPaymentService($gatewayProvider);
            }

            Session::flash('success_message', 'The integration has been added successfully.');
        } catch (\Exception $e) {
            Session::flash('success_message', 'An error has occurred, please contact the HitPay support team for assistance');
            Log::error($e);
        }

        return Response::redirectToRoute('dashboard.business.gateway.index', [
            $business->getKey()
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Business $business
     * @param \App\Business\GatewayProvider $gatewayProvider
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \ReflectionException
     */
    public function edit(Business $business, GatewayProvider $gatewayProvider, BusinessManagerInterface $businessManager)
    {
        Gate::inspect('view', [$gatewayProvider, $business])->authorize();

        $data = $this->formData($business, $businessManager);

        // only include GrabPay (Stripe) in available methods if it was enabled before

        return Response::view('dashboard.business.gateway-provider.form', compact('business', 'gatewayProvider', 'data'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param GatewayProviderRequest $request
     * @param \App\Business $business
     * @param \App\Business\GatewayProvider $gatewayProvider
     *
     * @param XeroPaymentService $paymentService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(GatewayProviderRequest $request, Business $business, GatewayProvider $gatewayProvider, XeroPaymentService $paymentService)
    {
        if (!Gate::allows('view-checkout', $business)) {
            Response::deny('You must setup your payment providers.');
        }


        try {
            $gatewayProvider = GatewayProviderManager::update($gatewayProvider, $request->all());

            $paymentService->createPaymentService($gatewayProvider);

            Session::flash('success_message', 'The integration \''.($gatewayProvider->name)
                .'\' has been updated successfully.');
        } catch (\Exception $e) {
            Session::flash('error_message', 'An error has occurred, please contact the HitPay support team for assistance');
            Log::error($e);
        }


        return Response::redirectToRoute('dashboard.business.gateway.index', [
            $business->getKey()
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Business $business
     * @param \App\Business\GatewayProvider $gatewayProvider
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function show(Business $business, GatewayProvider $gatewayProvider)
    {
        Gate::inspect('view', [$gatewayProvider, $business])->authorize();

        return Response::view('dashboard.business.gateway-provider.show', compact('business', 'gatewayProvider'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Business $business
     * @param \App\Business\GatewayProvider $gatewayProvider
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function destroy(Business $business, GatewayProvider $gatewayProvider)
    {
        Gate::inspect('delete', [$gatewayProvider, $business])->authorize();

        $providerName = $gatewayProvider->name;

        GatewayProviderManager::delete($gatewayProvider);

        Session::flash('success_message', 'The integration \''.$providerName.'\' has been deleted successfully.');

        return Response::redirectToRoute('dashboard.business.gateway.index', [
            $business->getKey()
        ]);
    }

    private function formData(Business $business, BusinessManagerInterface $businessManager)
    {
        // HIT-173: Remove wechat from integrations
        $methods = $businessManager->getByBusinessAvailablePaymentMethods($business, null, true);

        $methods = array_filter($methods, function ($m) {
          return $m !== PaymentMethodType::WECHAT;
        }, ARRAY_FILTER_USE_KEY);

        return [
            'providers' => PluginProvider::getAll(true),
            'methods'   => $methods
        ];
    }
}
