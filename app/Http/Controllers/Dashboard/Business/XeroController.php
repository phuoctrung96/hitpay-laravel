<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\Enumerations\Business\ChargeStatus;
use App\Enumerations\Business\PluginProvider;
use App\Http\Controllers\Controller;
use App\Business\Xero;
use App\Log;
use App\Manager\ApiKeyManager;
use App\Services\Xero\DisconnectService;
use App\Services\XeroApiFactory;
use App\XeroOrganization;
use Exception;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use XeroAPI\XeroPHP\Api\IdentityApi;
use XeroAPI\XeroPHP\ApiException;
use XeroAPI\XeroPHP\Configuration;
use XeroAPI\XeroPHP\Models\Accounting\Account;
use XeroAPI\XeroPHP\Models\Accounting\Organisation;
use XeroAPI\XeroPHP\Models\Accounting\Organisations;

class XeroController extends Controller
{

    public function showHome(Business $business)
    {
        return Response::view('dashboard.business.xero.index', compact('business'));
    }
    /**
     * @param Request $request
     * @param Business $business
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, DisconnectService $xeroDisconnectService, Business $business)
    {
        $paginator = $business->xeroLogs()->paginate(15);

        $xeroAccounts = [];
        $brandingThemes = [];
        $bankAccounts = [];
        if(!empty($business->xero_refresh_token)) {
            try {
                $xeroAccounts = app('xeroAccountsService')->getAccounts($business);
            } catch (Exception $apiException) {
                $xeroDisconnectService->disconnectBusinessFromXero($business);

                return redirect(route('dashboard.business.integration.xero.home', $business->id));
            }

            $brandingThemes = [];
            foreach (xero_branding_themes($business) as $theme) {
                $brandingThemes[$theme->getBrandingThemeId()] = $theme->getName();
            }

            foreach ($xeroAccounts as $xeroAccount) {
                if($xeroAccount['type'] == 'BANK') {
                    $bankAccounts[] = $xeroAccount;
                }
            }
        }


        $xeroAccounts = array_filter($xeroAccounts, function($account) {
            return $account['can_accept_payments'];
        });


        $pluginProviders = [];
        foreach (PluginProvider::CHANNELS as $channel) {
            if(in_array($channel, [PluginProvider::SHOPIFY, PluginProvider::WOOCOMMERCE])) {
                continue;
            }

            $pluginProviders[] = [
                'key' => $channel,
                'label' => Str::ucfirst(str_replace('_', ' ', $channel))
            ];
        }

        return Response::view('dashboard.business.xero.index', compact(
            'pluginProviders',
            'business',
            'paginator',
            'xeroAccounts',
            'brandingThemes',
            'bankAccounts'
        ));
    }

    /**
     * @param Business $business
     * @return \Illuminate\Http\Response
     */
    public function show(Business $business)
    {
        return Response::view('dashboard.business.xero', compact('business'));
    }

    /**
     *
     */
    public function xeroAuthorize(Business $business)
    {
        $xero = new Xero();
        $authorizeURL = $xero->authorize();
        \session()->put('xero_business_id', $business->getKey());
        return redirect($authorizeURL);
    }

    public function handleCallBack(Request $request)
    {
        $business_id = \session()->get('xero_business_id');
        /** @var Business $business */
        $business = Business::findOrFail($business_id);
        if (!isset($business_id))
        {
            App::abort(404);
        }
        if ($request->has('code'))
        {
            try {
                $xero = new Xero();
                $xero_email_address = null;
                $accessToken = $xero->provider->getAccessToken('authorization_code', [
                    'code' => $request->get('code')
                ]);
                $refreshToken = $accessToken->getRefreshToken(); // save only the refresh token for later use
                $config = Configuration::getDefaultConfiguration()->setAccessToken( (string)$accessToken->getToken() );
                $identityApi = new IdentityApi(
                    new Client(),
                    $config
                );
                $result = $identityApi->getConnections();
                $jwtToken =  $accessToken->getValues()["id_token"];
                $tokenParts = explode('.', $jwtToken);
                $profile = \GuzzleHttp\json_decode(base64_decode($tokenParts[1]));
                if (isset($profile->email))
                {
                    $xero_email_address = $profile->email;
                }

                if(Business::where('xero_email', $xero_email_address)->exists()) {
                    Session::flash('failed_message', 'Error! This email address '.$xero_email_address.' already connected to other Hitpay account.');
                    return redirect('/business/'.$business->id.'/integration/xero/home');
                }

                DB::beginTransaction();
                $business->saveXeroInfo($refreshToken, $result[0]->getTenantId(), $xero_email_address);

                $accountingApi = XeroApiFactory::makeAccountingApi($business->fresh());
                /** @var Organisations $organisations */
                $organisations = $accountingApi->getOrganisations($business->xero_tenant_id)->getOrganisations();
                /** @var Organisation $organisation */
                foreach ($organisations as $organisation) {
                    XeroOrganization::create([
                        'business_id' => $business->id,
                        'short_code' => $organisation->getShortCode(),
                        'name' => $organisation->getName(),
                    ]);
                }

                if($business->apiKeys()->count() == 0) {
                    ApiKeyManager::create($business);
                }

                DB::commit();
                Session::flash('success_message', 'Successfully authorized. ');

                return redirect('/business/'.$business->id.'/integration/xero/home');
            } catch (IdentityProviderException $exception) {
                \Illuminate\Support\Facades\Log::channel('xero')->error($exception);
                Session::flash('failed_message', 'Something went wrong, please try again later');
                DB::rollBack();

                return redirect('/business/'.$business->id.'/integration/xero/home');
            } catch (QueryException $exception) {
                \Illuminate\Support\Facades\Log::error($exception);
                Session::flash('failed_message', 'Something went wrong, this xero account already connected to another HitPay account');
                DB::rollBack();

                return redirect('/business/'.$business->id.'/integration/xero/home');
            }

        }
    }

    /**
     * @param Request $request
     * @param Business $business
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function saveSettings(Request $request, Business $business)
    {

        $requestData = $this->validate($request, [
            'sync_date' => 'required|date',
            'sales_account_type' => 'string',
            'refund_account_type' => 'required|string',
            'fee_account_type' => 'required|string',
            'invoice_grouping' => 'required|string',
            'disable_sales_feed' => 'required',
            'xero_sales_account_id' => 'required|string',
            'xero_refund_account_id' => 'sometimes',
            'xero_fee_account_id' => 'required|string',
            'paynow_btn_text' => 'required',
            'xero_branding_theme' => 'required',
            'xero_payout_account_id' => 'required',
            'channels' => 'sometimes',
        ]);
        try {
            if(empty($business->xero_bank_account_id)) {
                $accountName = 'HitPay Clearing Account';
                /** @var Collection $feeAccounts */
                $bankAccounts = app('xeroAccountsService')->getBankAccounts($business)->filter(function($account) use($accountName) {
                    return strpos($account['name'], $accountName) === 0;
                });

                if(!$bankAccounts->count()) {
                    $code = rand(0, 999);
                    $newAccount = new Account();
                    $newAccount->setCode($code);
                    $newAccount->setType('BANK');
                    $newAccount->setBankAccountNumber('1234567890');
                    $newAccount->setName($accountName);
                    $result = (XeroApiFactory::makeAccountingApi($business))
                        ->createAccount($business->xero_tenant_id, $newAccount);
                    $bankAccountId = $result->getAccounts()[0]->getAccountId();
                } else {
                    $bankAccountId = $bankAccounts->shift()['id'];
                }

            }

            $business->xero_payment_fee_account_id = $request->xero_fee_account_id;

            DB::beginTransaction();

            if(!empty($bankAccountId)) {
                $business->xero_bank_account_id = $bankAccountId;
            }

            $business->xero_sync_date = date('Y-m-d', strtotime($requestData['sync_date']));
            $business->xero_sales_account_type = $requestData['sales_account_type'];
            $business->xero_refund_account_type = $requestData['refund_account_type'];
            $business->xero_fee_account_type = $requestData['fee_account_type'];
            $business->xero_invoice_grouping = $requestData['invoice_grouping'];
            $business->xero_disable_sales_feed = $requestData['disable_sales_feed'];
            $business->xero_account_id = $requestData['xero_sales_account_id'];
            $business->xero_channels = $requestData['channels'];

            if(!empty($requestData['xero_refund_account_id'])) {
                $business->xero_refund_account_id = $requestData['xero_refund_account_id'];
            }

            $business->xero_fee_account_id = $requestData['xero_fee_account_id'];
            $business->paynow_btn_text = $requestData['paynow_btn_text'];
            $business->xero_branding_theme = $requestData['xero_branding_theme'];
            $business->xero_payout_account_id = $requestData['xero_payout_account_id'];
            $business->update();
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error($exception);

            Session::flash('success_message', 'An error has occurred, please contact the HitPay support team for assistance');
            return Response::json([
                'redirect_url' => URL::route('dashboard.business.integration.xero.home', $business->getKey()),
            ]);
        }

        Session::flash('success_message', 'Xero settings updated');
        return Response::json([
            'redirect_url' => URL::route('dashboard.business.integration.xero.home', $business->getKey()),
        ]);
    }
    public function disconnect(Business $business)
    {
        if (isset($business->id)) {
            try {
                XeroApiFactory::disconnect($business);
            } catch (Exception $exception) {

            }

            $business->disconnectXero();

            Session::flash('success_message', 'Xero account disconnected');
            return Response::json([
                'redirect_url' => URL::route('dashboard.business.integration.xero.home', $business->getKey()),
            ]);
        }
    }
}
