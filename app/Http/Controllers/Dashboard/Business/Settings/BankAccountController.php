<?php

namespace App\Http\Controllers\Dashboard\Business\Settings;

use App\Actions\Business\Settings\BankAccount\Destroy;
use App\Actions\Business\Settings\BankAccount\SetAsDefaultForHitPayPayout;
use App\Actions\Business\Settings\BankAccount\SetAsDefaultForStripePayout;
use App\Actions\Business\Settings\BankAccount\Store;
use App\Actions\Business\Settings\BankAccount\Update;
use App\Actions\Exceptions\BadRequest;
use App\Business;
use App\Http\Controllers\Controller;
use App\Http\Resources\Business\BankAccount as BankAccountResource;
use App\Models\Business\BankAccount;
use HitPay\Data\Countries;
use Illuminate\Http;
use Illuminate\Support\Facades;

class BankAccountController extends Controller
{
    /**
     * BankAccountController Constructor
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the homepage of the settings for bank accounts.
     *
     * @param  \App\Business  $business
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showHomepage(Business $business) : Http\Response
    {
        Facades\Gate::inspect('view', $business)->authorize();

        return Facades\Response::view('dashboard.business.settings.bank-accounts.home', [
            'business' => $business,
            'bankAccounts' => $business->bankAccounts()->get(),
        ]);
    }

    public function showCreatePage(Business $business) : Http\Response
    {
        Facades\Gate::inspect('update', $business)->authorize();

        $country = Countries::get($business->country);

        $banks = $country->banks()->toArray();

        return Facades\Response::view('dashboard.business.settings.bank-accounts.create', [
            'business' => $business,
            'banks' => $banks,
        ]);
    }

    /**
     * Create a new bank account.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Business  $business
     *
     * @return \App\Http\Resources\Business\BankAccount|\Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\GeneralException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    public function store(Http\Request $request, Business $business)
    {
        Facades\Gate::inspect('update', $business)->authorize();

        try {
            $bankAccount = Store::withBusiness($business)->data($request->all())->process();
        } catch (BadRequest $exception) {
            if ($request->wantsJson()) {
                return Facades\Response::json([
                    'message' => $exception->getMessage(),
                ], Http\Response::HTTP_BAD_REQUEST);
            }

            return Facades\Response::redirectToRoute('dashboard.business.settings.bank-accounts.homepage', [
                'business_id' => $business->getKey(),
            ])->with('error_message', $exception->getMessage());
        }

        $request->session()->flash('success_message', 'The bank account has been created successfully.');

        return new BankAccountResource($bankAccount);
    }

    /**
     * Show the edit bank account page.
     *
     * @param  \App\Business  $business
     * @param  \App\Models\Business\BankAccount  $bankAccount
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showEditPage(Business $business, BankAccount $bankAccount) : Http\Response
    {
        Facades\Gate::inspect('update', $business)->authorize();

        $country = Countries::get($business->country);

        $banks = $country->banks()->toArray();

        return Facades\Response::view(
            'dashboard.business.settings.bank-accounts.edit',
            compact('business', 'bankAccount', 'banks')
        );
    }

    /**
     * Update the bank account.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Business  $business
     * @param  \App\Models\Business\BankAccount  $bankAccount
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\GeneralException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Stripe\Exception\InvalidRequestException
     * @throws \Throwable
     */
    public function update(Http\Request $request, Business $business, BankAccount $bankAccount)
    {
        Facades\Gate::inspect('update', $business)->authorize();

        $data = $request->all();

        $data['use_in_stripe'] = true;

        Update::withBusiness($business)->bankAccount($bankAccount)->data($data)->process();

        $request->session()->flash('success_message', 'The bank account has been updated successfully.');

        return Facades\Response::json();
    }

    /**
     * Set default for selected payment provider.
     *
     * @param  \App\Business  $business
     * @param  \App\Models\Business\BankAccount  $bankAccount
     * @param  string  $paymentProvider
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\AccountNotFoundException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\GeneralException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Throwable
     */
    public function setDefaultFor(
        Business $business, BankAccount $bankAccount, string $paymentProvider
    ) : Http\JsonResponse {
        Facades\Gate::inspect('update', $business)->authorize();

        if ($paymentProvider === 'hitpay') {
            SetAsDefaultForHitPayPayout::withBusiness($business)->bankAccount($bankAccount)->process();

            $paymentProvider = 'HitPay';
        } elseif ($paymentProvider === 'stripe') {
            SetAsDefaultForStripePayout::withBusiness($business)->bankAccount($bankAccount)->process();

            $paymentProvider = 'Stripe';
        } else {
            Facades\App::abort(400);
        }

        return Facades\Response::json([
            'message' => "The bank account has been set as default for {$paymentProvider}."
        ]);
    }

    /**
     * "Delete" the bank account.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Business  $business
     * @param  \App\Models\Business\BankAccount  $bankAccount
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\GeneralException
     * @throws \HitPay\Stripe\CustomAccount\Exceptions\InvalidStateException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Stripe\Exception\InvalidRequestException
     * @throws \Throwable
     */
    public function destroy(Http\Request $request, Business $business, BankAccount $bankAccount)
    {
        Facades\Gate::inspect('update', $business)->authorize();

        try {
            Destroy::withBusiness($business)->bankAccount($bankAccount)->process();
        } catch (BadRequest $exception) {
            if ($request->wantsJson()) {
                return Facades\Response::json([
                    'message' => $exception->getMessage(),
                ], 400);
            }

            return Facades\Response::redirectToRoute('dashboard.business.settings.bank-accounts.homepage', [
                'business_id' => $business->getKey(),
            ])->with('error_message', $exception->getMessage(),);
        }

        if ($request->wantsJson()) {
            return Facades\Response::noContent();
        }

        return Facades\Response::redirectToRoute('dashboard.business.settings.bank-accounts.homepage', [
            'business_id' => $business->getKey(),
        ])->with('success_message', 'The bank account has been deleted successfully.');
    }
}
