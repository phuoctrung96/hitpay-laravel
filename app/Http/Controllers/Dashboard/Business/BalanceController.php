<?php

namespace App\Http\Controllers\Dashboard\Business;

use App\Actions\Business\Stripe\BalanceTransaction;
use App\Business;
use App\Enumerations\Business\PaymentMethodType;
use App\Enumerations\Business\SupportedCurrencyCode;
use App\Enumerations\Business\Wallet\Type;
use App\Enumerations\CurrencyCode;
use App\Enumerations\PaymentProvider;
use App\Helpers\Pagination;
use App\Helpers\Topup;
use App\Http\Controllers\Controller;
use App\Jobs\Wallet\PayoutToBank;
use App\Models\Business\BankAccount;
use HitPay\PayNow\Generator;
use HitPay\Stripe\CustomAccount\Balance\Retrieve;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BalanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function showHomepage(Business $business)
    {
        Gate::inspect('canManageWallets', $business)->authorize();

        return Response::redirectToRoute('dashboard.business.balance.currency', [
            $business->id,
            $business->currency,
        ]);
    }

    public function showCurrencyPage(Business $business, string $currency)
    {
        Gate::inspect('canManageWallets', $business)->authorize();

        $wallets = $this->getWalletsForCurrency($business, $currency);

        $viewData = compact('business', 'currency', 'wallets');

        if ($business->usingStripeCustomAccount()) {
            $viewData['stripeCustomAccountBalance'] = Retrieve::new($business->payment_provider)->setBusiness($business)->handle();
        }

        return Response::view('dashboard.business.balance.home', $viewData);
    }

    public function showWalletPage(Request $request, Business $business, string $currency, string $type)
    {
        Gate::inspect('canManageWallets', $business)->authorize();

        if (!Business\Wallet::isSupportingType($type) || !Business\Wallet::isSupportingCurrency($currency)) {
            App::abort(404);
        }

        $perPage = $request->get('perPage', Pagination::getDefaultPerPage());

        $wallet = $business->wallet($type, $currency);

        $availableWallet = $business->wallet(Type::AVAILABLE, $currency);
        $availableWalletBalance = $availableWallet->balance ?? 0;

        $reserveWallet = $business->wallet(Type::RESERVE, $currency);
        $reserveWalletBalance = $reserveWallet->balance ?? 0;

        $paginator = Business\Wallet\Transaction::query()
            ->where([
                'business_id' => $wallet->business_id,
                'wallet_id' => $wallet->id,
            ])
            ->with([
                'wallet',
                'relatedWallet',
                'relatable',
            ])
            ->when(request('event'), function ($query) {
                return $query->where('event', request('event'));
            })
            ->orderByDesc('id')
            ->paginate($perPage);

        $canSendBalanceToBank = Gate::inspect('canSendBalanceToBank', $business)->allowed();

        return Response::view('dashboard.business.balance.wallet', compact(
            'business',
            'currency',
            'wallet',
            'availableWalletBalance',
            'reserveWalletBalance',
            'canSendBalanceToBank',
            'paginator',
            'perPage'
        ));
    }

    public function updateReserveWallet(Request $request, Business $business, string $currency, string $transferType)
    {
        if ($transferType !== 'to_available' && $transferType !== 'from_available') {
            App::abort(404);
        }

        Gate::inspect('canManageWallets', $business)->authorize();

        if (!Business\Wallet::isSupportingCurrency($currency)) {
            App::abort(404);
        }

        $reserveWallet = $business->wallet(Type::RESERVE, $currency);
        $availableWallet = $business->wallet(Type::AVAILABLE, $currency);

        if (in_array($currency, SupportedCurrencyCode::zeroDecimal())) {
            $rule = 'int';
        } else {
            $rule = 'decimal:0,2';
        }

        if ($transferType === 'to_available') {
            $maxValue = getReadableAmountByCurrency($currency, $reserveWallet->balance);
        } else {
            $maxValue = getReadableAmountByCurrency($currency, $availableWallet->balance);
        }

        $data = $request->validate([
            'amount' => [
                'required',
                'numeric',
                $rule,
                "max:{$maxValue}",
            ],
        ]);

        if (in_array($currency, SupportedCurrencyCode::normal())) {
            $data['amount'] = getRealAmountForCurrency($currency, $data['amount']);
        }

        if ($transferType === 'to_available') {
            $reserveWallet->transfer($availableWallet, $data['amount']);
        } else {
            $availableWallet->transfer($reserveWallet, $data['amount']);
        }

        return response()->json($data);

        // do transfer

        // return Response::redirectToRoute('dashboard.business.balance.wallet', [
        //     $business->id,
        //     $wallet->currency,
        //     $wallet->type,
        // ]);
    }

    public function updateAvailableWallet(Request $request, Business $business)
    {
        Gate::inspect('canManageWallets', $business)->authorize();

        try {
            $data = $request->validate([
                'auto_pay_to_bank' => [
                    'nullable',
                    'in:on,off',
                ],
                'auto_pay_to_bank_day' => [
                    'required_if:auto_pay_to_bank,on',
                    Rule::in([ 'daily', 'weekly', 'monthly' ]),
                ],
                'auto_pay_to_bank_weekly_day' => [
                    'required_if:auto_pay_to_bank_period,weekly',
                    Rule::in([ 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' ]),
                ],
                'auto_pay_to_bank_time' => [
                    'required_if:auto_pay_to_bank_period,daily',
                    'in:00:00:00,09:30:00',
                ],
            ]);
        } catch (ValidationException $exception) {
            dd($request->all(), $exception->errors());
        }
        if ($data['auto_pay_to_bank_day'] === 'monthly') {
            $autoPayToBankDay = 'monthly_1';
        } elseif ($data['auto_pay_to_bank_day'] === 'weekly') {
            $autoPayToBankDay = "weekly_{$data['auto_pay_to_bank_weekly_day']}";
        } else {
            $autoPayToBankDay = 'daily';
        }

        // if auto-pay to bank is off, set the value default to daily and 09:30:00.

        $business->auto_pay_to_bank = ( $data['auto_pay_to_bank'] ?? 'off' ) === 'on';
        $business->auto_pay_to_bank_day = $business->auto_pay_to_bank ? $autoPayToBankDay : 'daily';
        $business->auto_pay_to_bank_time = $autoPayToBankDay !== 'daily'
            ? '09:30:00'
            : $data['auto_pay_to_bank_time'] ?? '09:30:00';

        $business->save();

        return Redirect::back();
    }

    public function requestPayout(Business $business, string $currency)
    {
        Gate::inspect('canSendBalanceToBank', $business)->authorize();

        if (!Business\Wallet::isSupportingCurrency($currency)) {
            App::abort(404);
        }

        $business->load([
            'paymentProviders' => function (HasMany $query) {
                $query->where('payment_provider', PaymentProvider::DBS_SINGAPORE);
            },
        ]);

        $paymentProvidersCount = $business->paymentProviders->count();

        if ($paymentProvidersCount < 1) {
            return Response::json([
                'message' => 'You have to set up your bank account to withdraw.',
            ], 400);
        }

        if ($paymentProvidersCount > 1) {
            Log::critical('Business # '.$business->getKey().' is detected with two "dbs_sg" account.');
        }

        $latestTransfer = $business->transfers()
            ->where('payment_provider_transfer_method', 'wallet_fast')
            ->orderByDesc('created_at')->first();

        if ($latestTransfer && $latestTransfer->created_at->isToday()) {
            return Response::json([
                'message' => 'You can have only 1 payout per day.',
            ], 400);
        }

        $wallet = $business->availableBalance($currency);

        if ($wallet->balance < 100) {
            return Response::json([
                'message' => 'Your balance must be more than '.getFormattedAmount($currency, 100).' to withdraw.',
            ], 400);
        }

        $bankAccount = $business->bankAccounts()->where([
            'country' => $business->country,
            'currency' => $business->currency,
            'hitpay_default' => true,
        ])->first();

        if (!($bankAccount instanceof BankAccount)) {
            return Response::json([
                'message' => 'Your must setup a bank account to withdraw.',
            ], 400);
        }

        $transfer = $business->payoutToBank($business->paymentProviders->first(), $currency, $bankAccount);

        PayoutToBank::dispatch($transfer);

        return Response::json([
            'message' => 'Your payout is on the way to your bank.',
        ]);
    }

    public function showTransactionsPage(Request $request, Business $business, string $currency)
    {
        Gate::inspect('canManageWallets', $business)->authorize();

        $perPage = $request->get('perPage', Pagination::getDefaultPerPage());

        $wallets = $business->wallets()->where('currency', $currency)->get();

        if ($wallets->count()) {
            $totalBalance = $wallets->sum('balance');
            $paginator = $business->walletTransactions()
                ->with('wallet', 'relatedWallet', 'relatable')
                ->whereIn('wallet_id', $wallets->pluck('id')->toArray())
                ->orderByDesc('id')->paginate($perPage);
        } else {
            $totalBalance = 0;
            $paginator = new LengthAwarePaginator([], 0, $perPage, 1);
        }

        return Response::view('dashboard.business.balance.transactions', compact(
            'business',
            'currency',
            'totalBalance',
            'paginator',
            'perPage'
        ));
    }

    /**
     * Show the breakdown for Stripe Balance Transactions.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Business  $business
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showStripeTransactionsPage(Request $request, Business $business)
    {
        Gate::inspect('canManageWallets', $business)->authorize();

        $perPage = $request->get('perPage', Pagination::getDefaultPerPage());

        if (!in_array($perPage, Pagination::AVAILABLE_PAGE_NUMBER)) {
            $perPage = Arr::first(Pagination::AVAILABLE_PAGE_NUMBER);
        }

        $response = BalanceTransaction\Index::withBusiness($business)
            ->setFirst($request->input('ending_before'))
            ->setLast($request->input('start_after'))
            ->setPerPage($perPage)
            ->process();

        $balanceTransactionFirstId = $response['ending_before'];
        $balanceTransactionLastId = $response['start_after'];
        $balanceTransactions = $response['data']->map(function (array $item) {
            if ($item['charge'] instanceof Business\Charge) {
                if ($item['reporting_category'] === 'charge') {
                    $item['description'] = "Received for charge #{$item['charge']->getKey()}";
                } elseif ($item['reporting_category'] === 'refund') {
                    $item['description'] = "Refunded for charge #{$item['charge']->getKey()}";
                } else {
                    $item['description'] = "Processed a '{$item['reporting_category']}' for charge #{$item['charge']->getKey()}";
                }
            } elseif (is_array($item['payout'])) {
                $item['description'] = "Payout";
            } elseif ($item['reporting_category'] === 'charge') {
                $item['description'] = "Received for an unknown charge";
            } elseif ($item['reporting_category'] === 'refund') {
                $item['description'] = "Refunded for an unknown charge";
            } else {
                $item['description'] = "Processed a '{$item['reporting_category']}' for an unknown charge";
            }

            return $item;
        });

        return Response::view('dashboard.business.balance.stripe-transactions', compact(
            'business',
            'balanceTransactions',
            'balanceTransactionFirstId',
            'balanceTransactionLastId',
            'perPage',
        ));
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Business  $business
     * @param  string  $currency
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\HitPayLogicException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     */
    public function generateTopUpIntent(Request $request, Business $business, string $currency)
    {
        Gate::inspect('canManageWallets', $business)->authorize();

        if ($currency !== CurrencyCode::SGD) {
            throw ValidationException::withMessages([
                'amount' => 'The selected wallet is unavailable.',
            ]);
        }

        $wallet = $business->wallet(Type::AVAILABLE, $currency, false);

        if (!$wallet instanceof Business\Wallet) {
            throw ValidationException::withMessages([
                'amount' => 'The selected wallet is unavailable.',
            ]);
        }

        $data = $this->validate($request, [
            'amount' => [
                'required',
                'numeric',
                'between:0.01,' . Topup::MAX_AMOUNT,
            ],
        ]);

        $amountToBeAdded = getRealAmountForCurrency($currency, $data['amount']);

        $paynow = Generator::new('top-up-wallet')
            ->setAmount($amountToBeAdded)
            ->setExpiryAt(Date::now()->addSeconds(300))
            ->setMerchantName($business->getName());

        $topUpIntent = $business->topUpIntents()->create([
            'business_id' => $business->getKey(),
            'payment_provider' => PaymentProvider::DBS_SINGAPORE,
            'payment_provider_object_type' => 'inward_credit_notification',
            'payment_provider_object_id' => $paynow->getReference(),
            'payment_provider_method' => 'paynow_online',
            'currency' => $currency,
            'amount' => $amountToBeAdded,
            'status' => 'pending',
            'data' => [
                'data' => $paynow->generate(),
            ],
            'expires_at' => Date::now()->addMinutes(15),
        ]);

        return Response::json([
            'id' => $topUpIntent->id,
            'paynow_data' => $topUpIntent->data['data'],
            'amount' => getReadableAmountByCurrency($topUpIntent->currency, $topUpIntent->amount),
        ]);
    }

    /**
     * @param  \App\Business  $business
     * @param  string  $topUpIntentId
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function getTopUpIntent(Business $business, string $topUpIntentId)
    {
        Gate::inspect('canManageWallets', $business)->authorize();

        $topUpIntent = $business->topUpIntents()->findOrFail($topUpIntentId);

        $data['id'] = $topUpIntent->getKey();
        $data['wallet_id'] = $topUpIntent->business_wallet_id;
        $data['object_type'] = $topUpIntent->payment_provider_object_type;
        $data['object_id'] = $topUpIntent->payment_provider_object_id;
        $data['method'] = $topUpIntent->payment_provider_method;
        $data['currency'] = $topUpIntent->currency;
        $data['amount'] = $topUpIntent->amount;
        $data['status'] = $topUpIntent->status;

        if ($data['status'] === 'failed') {
            $data['failed_reason'] = $topUpIntent->failed_reason;
        }

        if ($data['method'] === PaymentMethodType::PAYNOW && $data['status'] === 'pending') {
            $data[PaymentMethodType::PAYNOW] = [
                'qr_code_data' => $topUpIntent->data['data'],
            ];
        }

        $data['created_at'] = $topUpIntent->created_at->toAtomString();
        $data['updated_at'] = $topUpIntent->updated_at->toAtomString();

        if ($expiresAt = $topUpIntent->expires_at) {
            $data['expires_at'] = $expiresAt->toAtomString();
        }

        return Response::json($data);
    }

    protected function getWalletsForCurrency(Business $business, string $currency) : array
    {
        $business->load('wallets');

        foreach (Type::toArray() as $value) {
            $wallets[$value] = $business->wallets->where('currency', $currency)->where('type', $value)->first();

            if (!$wallets[$value] instanceof Business\Wallet) {
                $wallets[$value] = $business->wallet($value, $currency);
            }
        }

        return $wallets ?? [];
    }

    public function export(Request $request, Business $business)
    {
        Gate::inspect('canManageWallets', $business)->authorize();
    }
}
