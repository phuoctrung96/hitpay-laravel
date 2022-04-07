<?php

namespace App\Http\Controllers\Admin;

use App\Business;
use App\Enumerations\Business\Wallet\Type;
use App\Http\Controllers\Controller;
use Illuminate\Http;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\ValidationException;

class BusinessWalletController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role');
    }

    /**
     * Show wallet.
     *
     * @param  \App\Business  $business
     * @param  string  $currency
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showWallet(Business $business, string $currency) : Http\Response
    {
        Gate::inspect('view', $business)->authorize();

        $wallets = $business->wallets()->where('currency', $currency)->get();

        if ($wallets->count()) {
            $totalBalance = $wallets->sum('balance');
            $paginator = $business->walletTransactions()
                ->with([
                    'wallet',
                    'relatedWallet',
                    'relatable',
                ])
                ->whereIn('wallet_id', $wallets->pluck('id')->toArray())
                ->orderByDesc('id')
                ->paginate(50);
        } else {
            $totalBalance = 0;
            $paginator = new LengthAwarePaginator([], 0, 50, 1);
        }

        $wallets = $business->wallets()->where('currency', $currency)->get();

        $availableWallet = $wallets->where('type', Type::AVAILABLE)->first();
        $availableWalletBalance = $availableWallet->balance ?? 0;

        $depositWallet = $wallets->where('type', Type::DEPOSIT)->first();
        $depositWalletBalance = $depositWallet->balance ?? 0;
        $depositWalletReserveBalance = $depositWallet->reserve_balance ?? 0;

        return Response::view('admin.business.wallet-transactions', compact(
            'business',
            'currency',
            'totalBalance',
            'availableWalletBalance',
            'depositWalletBalance',
            'depositWalletReserveBalance',
            'paginator'
        ));
    }

    /**
     * Do administrative top up.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Business  $business
     * @param  string  $currency
     *
     * @throws \App\Exceptions\WalletException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function add(Http\Request $request, Business $business, string $currency)
    {
        Gate::inspect('view', $business)->authorize();

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
                'decimal:0,2',
            ],
            'description' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        $business->administrativeTopUp(
            $currency,
            getRealAmountForCurrency($currency, $data['amount']),
            $data['description']
        );

        return Response::json([
            'message' => 'Top up successfully.',
        ]);
    }

    /**
     * Do administrative deduction.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Business  $business
     * @param  string  $currency
     *
     * @throws \App\Exceptions\WalletException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function deduct(Http\Request $request, Business $business, string $currency)
    {
        Gate::inspect('view', $business)->authorize();

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
                'decimal:0,2',
                'max:'.getReadableAmountByCurrency($currency, $wallet->balance),
            ],
            'description' => [
                'required',
                'string',
                'max:255',
            ],
        ], [
            'amount.max' => 'The amount may not be greater than '.getFormattedAmount($currency, $wallet->balance).'.',
        ]);

        $business->administrativeDeduction(
            $currency,
            getRealAmountForCurrency($currency, $data['amount']),
            $data['description']
        );

        return Response::json([
            'message' => 'Deduction successfully.',
        ]);
    }

    public function setDeposit(Http\Request $request, Business $business, string $currency)
    {
        Gate::inspect('update', $business)->authorize();

        $depositWallet = $business->wallet(Type::DEPOSIT, $currency);

        $data = $this->validate($request, [
            'amount' => [
                'required',
                'numeric',
                'decimal:0,2',
            ],
        ]);

        $depositWallet->reserve_balance = getRealAmountForCurrency($currency, $data['amount']);
        $depositWallet->save();

        $transferable = $depositWallet->balance - $depositWallet->reserve_balance;

        if ($transferable > 0) {
            $availableWallet = $business->wallet(Type::AVAILABLE, $currency);

            $depositWallet->transfer($availableWallet, $transferable);
        } elseif ($transferable < 0) {
            $availableWallet = $business->wallet(Type::AVAILABLE, $currency);
            $transferable = abs($transferable);

            if ($availableWallet->balance > 0) {
                if ($transferable >= $availableWallet->balance) {
                    $availableWallet->transfer($depositWallet, $availableWallet->balance);
                } elseif ($transferable < $availableWallet->balance) {
                    if ($availableWallet->balance - $transferable > 0) {
                        $availableWallet->transfer($depositWallet, $transferable);
                    } elseif ($availableWallet->balance - $transferable < 0) {
                        $availableWallet->transfer($depositWallet, $availableWallet->balance);
                    }
                }
            }
        }

        return Response::json([
            'message' => 'Deposit set successfully.',
        ]);
    }
}
