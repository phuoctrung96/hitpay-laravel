<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Business;
use App\Http\Controllers\Controller;
use App\Services\Quickbooks\AccountsManager;
use App\Services\Quickbooks\AuthorizationService;
use App\Services\Quickbooks\CompaniesManager;
use App\Services\Quickbooks\ManagerFactory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use QuickBooksOnline\API\Data\IPPAccount;
use QuickBooksOnline\API\Exception\ServiceException;
use QuickBooksOnline\API\Facades\Vendor;
use Session;

/**
 * Class QuickbooksController
 * @package App\Http\Controllers\Dashboard\Business
 */
class QuickbooksController extends Controller
{
    /**
     * @param Business $business
     * @return mixed
     */
    public function index(Business $business)
    {
        $accounts = [];

        try {
            if($business->quickbooksIntegration) {
                /** @var IPPAccount[] $accounts */
                $accounts = collect(ManagerFactory::makeAccountsManager($business->quickbooksIntegration)
                    ->get());
                $accounts = $accounts->filter(function(IPPAccount $account) {
                    return in_array($account->AccountType, ['Other Current Asset', 'Bank', 'Credit Card']);
                });

            }
        } catch (\QuickBooksOnline\API\Exception\ServiceException $exception) {
            $business->quickbooksIntegration->delete();
            $business->fresh();
        }


        return view('dashboard.business.quickbooks.index', compact('business', 'accounts'));
    }

    public function quickbooksAuthorize(AuthorizationService $authorizationService, Business $business)
    {
        session()->put('quickbooks_business_id', $business->getKey());

        return redirect($authorizationService->getAuthorizationUrl());
    }

    public function handleCallBack(Request $request, AuthorizationService $authorizationService)
    {
        /** @var Business $business */
        $business = Business::findOrFail(session()->get('quickbooks_business_id'));
        if(!$request->has('code') || !$request->has('realmId')) {
            abort(\Illuminate\Http\Response::HTTP_BAD_REQUEST);
        }

        try {
            $token = $authorizationService->exchangeAuthorizationCodeForToken($request->input('code'), $request->input('realmId'));


            $business->quickbooksIntegration()->create([
                'refresh_token' => $token->getRefreshToken(),
                'access_token' => $token->getAccessToken(),
                'access_token_expires_at' => Carbon::parse($token->getAccessTokenExpiresAt()),
                'realm_id' => $token->getRealmID(),
                'organization' => '',
                'email' => '',
            ]);

            $business->load('quickbooksIntegration');

            $company = ManagerFactory::makeCompaniesManager($business->quickbooksIntegration)->find($token->getRealmID());

            $business->quickbooksIntegration->update([
                'organization' => $company->CompanyName,
                'email' => $company->CompanyEmailAddr
            ]);

            Session::flash('success_message', 'Successfully authorized');

            return redirect(route('dashboard.business.integration.quickbooks.home', $business));
        } catch (ServiceException $exception) {
            Session::flash('failed_message', 'Something went wrong.' . $exception->getMessage());

            return redirect(route('dashboard.business.integration.quickbooks.home', $business));
        }
    }

    public function disconnect(Request $request, Business $business, AuthorizationService $authorizationService)
    {
        try {
            $authorizationService->revokeToken($business->quickbooksIntegration->access_token);
        } catch (ServiceException $exception) {

        }

        $business->quickbooksIntegration()->delete();
        Session::flash('success_message', 'Your account was successfully disconnected from quickbooks');

        return redirect(route('dashboard.business.integration.quickbooks.home', $business));
    }

    public function saveSettings(Request $request, Business $business)
    {
        if(!$business->quickbooksIntegration) {
            Session::flash('failed_message', 'Something went wrong. Your account is not connected to Quickbooks');

            return redirect(route('dashboard.business.integration.quickbooks.home', $business));
        }

        $attributes = $this->validate($request, [
            'sales_account_id' => 'required',
            'refund_account_id' => 'sometimes',
//            'fee_account_id' => 'required',
            'initial_sync_date' => 'sometimes'
        ]);

        $business->quickbooksIntegration->update($attributes);

        Session::flash('success_message', 'Quickbooks settings updated');

        return redirect(route('dashboard.business.integration.quickbooks.home', $business));
    }
}
