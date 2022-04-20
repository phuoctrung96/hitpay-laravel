<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Actions\Business\Payout\DBS\Retrieve;
use App\Actions\Business\Payout\Stripe\RetrieveCustomConnect;
use App\Business;
use App\Business\PaymentProvider;
use App\Business\Transfer;
use App\Enumerations\CountryCode;
use App\Enumerations\PaymentProvider as PaymentProviderEnum;
use App\Helpers\Pagination;
use App\Http\Controllers\Controller;
use App\Jobs\SendExportedHitPayPayoutBreakdown;
use App\Jobs\SendExportedTransfers;
use App\Jobs\SetCustomPricingFromPartner;
use App\Notifications\AlertPayNowAccountChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PayNowController extends Controller
{
    /**
     * PayNowController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function showHomepage(Business $business, Request $request)
    {
        Gate::inspect('view', $business)->authorize();

        $data = $this->validate($request, [
          'success_message' => 'string|max:64|nullable'
        ]);

        $businessBankAccount = $business->bankAccounts()->first();

        $providers = $business->paymentProviders()->whereNotNull('payment_provider_account_id')->get();

        /** @var \App\Business\PaymentProvider $payNowProvider */
        $payNowProvider = $providers->where('payment_provider', PaymentProviderEnum::DBS_SINGAPORE)->first();

        if ($payNowProvider) {
          $payNowProvider = [
              'company_uen' => $payNowProvider->data['company']['uen'] ?? null,
              'company_name' => $payNowProvider->data['company']['name'] ?? null,
          ];
        }

        return Response::view('dashboard.business.payment-providers.paynow', [
            'business' => $business,
            'provider' => $payNowProvider,
            'success_message' => empty($data['success_message']) ? '' : $data['success_message'],
            'businessBankAccount' => $businessBankAccount
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function setup(Request $request, Business $business)
    {
        Gate::inspect('update', $business)->authorize();

        $providers = $business->paymentProviders()->whereNotNull('payment_provider_account_id')->get();

        $data = $this->validate($request, [
            'company_uen' => 'required|string',
            'company_name' => 'required|string',
            'password' => 'required|string',
        ]);

        if (!Hash::check($data['password'], Auth::user()->password)) {
            throw ValidationException::withMessages([
                'password' => 'The password is incorrect.',
            ]);
        }

        /** @var \App\Business\PaymentProvider $provider */
        $provider = $providers->where('payment_provider', PaymentProviderEnum::DBS_SINGAPORE)->first();

        $isExisting = $provider instanceof PaymentProvider;

        if (!$isExisting) {
            $provider = new PaymentProvider;
        }

        $provider->payment_provider = PaymentProviderEnum::DBS_SINGAPORE;
        $provider->payment_provider_account_id = "hitpay@{$provider->business_id}";
        $provider->onboarding_status = 'success';
        $provider->data = [
            'company' => [
                'name' => $data['company_name'],
                'uen' => $data['company_uen'],
            ],
        ];

        if (!$isExisting) {
            $business->paymentProviders()->save($provider);

            if($business->partner) {
                dispatch(new SetCustomPricingFromPartner($business->partner, $provider));
            }
        } else {
            $provider->save();
        }

        if ($provider->wasChanged()) {
            $provider->business->notify(new AlertPayNowAccountChanged);
        }

        return Response::json([
            'success_message' => 'You have setup PayNow successfully.',
        ]);
    }

    /**
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Response
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \ReflectionException
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Exception
     */
    public function payout(Request $request, Business $business): \Illuminate\Http\Response
    {
        Gate::inspect('view', $business)->authorize();

        $perPage = $request->get('perPage', Pagination::getDefaultPerPage());

        if (!in_array($business->country, [CountryCode::SINGAPORE, CountryCode::MALAYSIA])) {
            throw new \Exception("country {$business->country} not yet support for payouts");
        }

        $actionData = Retrieve::withBusiness($business)->setPerPage($perPage)->process();

        $transfers = $actionData['transfers'] ?? null;

        $provider = $actionData['provider'] ?? null;

        return Response::view('dashboard.business.paynow-payout', [
            'business' => $business,
            'paginator' => $transfers,
            'perPage' => $perPage,
            'provider' => $provider
        ]);
    }

    /**
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Response
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \ReflectionException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function payoutShow(Business $business, Transfer $transfer)
    {
        Gate::inspect('view', $business)->authorize();

        return Response::view('dashboard.business.paynow-payout-show', [
            'business' => $business,
            'transfer' => $transfer,
        ]);
    }

    public function downloadPayoutDetails(Business $business, Transfer $transfer)
    {
        Gate::inspect('view', $business)->authorize();

        return Response::streamDownload(function () use ($transfer) {
            echo $transfer->generateCsv();
        }, "transfer-{$transfer->getKey()}.csv");
    }

    /**
     * Export transfer for DBS payouts.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function export(Request $request, Business $business)
    {
        Gate::inspect('view', $business)->authorize();

        $data = $this->validate($request, [
            'starts_at' => [
                'required',
                'date_format:Y-m-d',
            ],
            'ends_at' => [
                'required',
                'date_format:Y-m-d',
            ],
        ]);

        $charges = $business->transfers()->whereIn('payment_provider', [
            PaymentProviderEnum::DBS_SINGAPORE,
        ]);

        $fromDate = Date::parse($data['starts_at']);
        $toDate = Date::parse($data['ends_at']);

        if ($fromDate->gt($toDate)) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $charges->whereDate('created_at', '>=', $fromDate->startOfDay()->toDateTimeString());
        $charges->whereDate('created_at', '<=', $toDate->endOfDay()->toDateTimeString());

        if ($charges->count() < 1) {
            App::abort(422, 'You don\'t any PayNow payout between these date.');
        }

        SendExportedTransfers::dispatch($business, [
            'from_date' => $data['starts_at'],
            'to_date' => $data['ends_at'],
        ]);

        return Response::json([
            'success' => true,
        ]);
    }

    public function exportBreakdown(Request $request, Business $business)
    {
        Gate::inspect('view', $business)->authorize();

        $data = $this->validate($request, [
            'starts_at' => [ 'required', 'date_format:Y-m-d H:i:s' ],
            'ends_at' => [ 'required', 'date_format:Y-m-d H:i:s' ],
        ]);

        $fromDate = Date::parse($data['starts_at']);
        $toDate = Date::parse($data['ends_at']);

        if ($fromDate->gt($toDate)) {
            [ $fromDate, $toDate ] = [ $toDate, $fromDate ];
        }

        if ($fromDate->isBefore(Date::createFromDate('2021', '08', '01')->startOfDay())) {
            App::abort(422, 'You can\'t export payout breakdown before 1st August 2021.');
        }

        $wallet = $business->availableBalance($business->currency);

        $transactions = $wallet->transactions();

        $transactions->whereBetween('created_at', [ $fromDate->toDateTimeString(), $toDate->toDateTimeString() ]);

        if ($transactions->count() < 1) {
            App::abort(422, 'You don\'t any payout breakdown between these dates.');
        }

        SendExportedHitPayPayoutBreakdown::dispatch($business, [
            'from_date' => $data['starts_at'],
            'to_date' => $data['ends_at'],
        ]);

        return Response::json([
            'success' => true,
        ]);
    }
}
