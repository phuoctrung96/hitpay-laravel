@extends('layouts.business', [
    'title' => 'HitPay Balance'
])

@section('business-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h3 class="font-weight-bold mb-3">HitPay Balance</h3>
                    @if(auth()->user()->businessPartner instanceof \App\Models\BusinessPartner)
                        <p>All commissions will be added to your <b>Available Balance</b></p>
                    @else
                        <p>All PayNow QR, GIRO, GrabPay, PayLater by Grab, Zip and Shopee Pay Transactions get added to your HitPay Balance.</p>
                    @endif
                    <p class="small">
                        <a href="{{ route('dashboard.business.balance.transactions', [
                            $business->id,
                            $currency,
                        ]) }}">
                            View all transaction
                        </a>
                    </p>
                    <p class="small mb-0">
                        @if ($currency === \App\Enumerations\CurrencyCode::SGD)
                            <balance-top-up business-id="{{ $business->getKey() }}" currency="{{ $currency }}"></balance-top-up>
                        @endif
                    </p>
                </div>
                <a href="{{ route('dashboard.business.balance.wallet', [
                    $business->id,
                    $business->currency,
                    'available'
                ]) }}">
                    <div class="card-body border-top bg-light p-4">
                        <div class="row" style="font-size: 110%">
                            <div class="col-8">
                                <p class="font-weight-bold mb-1">Available to pay out</p>
                                <p class="small text-secondary mb-1">(Minimum Payout Value = SGD 1)</p>
                                <p class="small text-secondary mb-0">Includes funds that can be paid out now. You can choose to automatically payout or manually payout. Automatic payouts follow the regular payout schedule.</p>
                            </div>
                            <div class="col-4 font-weight-bold text-right">
                                @if ($wallets['available']->balance < 0)
                                    <span class="text-danger">- {{ strtoupper($wallets['available']->currency) }} {{ getFormattedAmount($wallets['available']->currency, abs($wallets['available']->balance), false) }}</span>
                                @elseif ($wallets['available']->balance > 0)
                                    <span class="text-success">{{ strtoupper($wallets['available']->currency) }} {{ getFormattedAmount($wallets['available']->currency, $wallets['available']->balance, false) }}</span>
                                @else
                                    {{ strtoupper($wallets['available']->currency) }} {{ getFormattedAmount($wallets['available']->currency, $wallets['available']->balance, false) }}
                                @endif
                            </div>
                        </div>
                    </div>
                </a>
                <a href="{{ route('dashboard.business.balance.wallet', [
                    $business->id,
                    $business->currency,
                    'pending'
                ]) }}">
                    <div class="card-body border-top bg-light p-4">
                        <div class="row">
                            <div class="col-8">
                                <p class="mb-1">On The Way To Your Bank</p>
                                <p class="small text-secondary mb-0">Includes funds that are not yet available to pay out.</p>
                            </div>
                            <div class="col-4 font-weight-bold text-right">
                                @if ($wallets['pending']->balance < 0)
                                    <span class="text-danger">- {{ strtoupper($wallets['pending']->currency) }} {{ getFormattedAmount($wallets['pending']->currency, abs($wallets['pending']->balance), false) }}</span>
                                @else
                                    {{ strtoupper($wallets['pending']->currency) }} {{ getFormattedAmount($wallets['pending']->currency, $wallets['pending']->balance, false) }}
                                @endif
                            </div>
                        </div>
                    </div>
                </a>
                <a href="{{ route('dashboard.business.balance.wallet', [
                    $business->id,
                    $business->currency,
                    'reserve'
                ]) }}">
                    <div class="card-body border-top bg-light p-4">
                        <div class="row">
                            <div class="col-8">
                                <p class="mb-1">Reserve</p>
                                <p class="small text-secondary mb-0">Includes funds that can be used to pay for refunds and chargebacks. Funds can be added to the Reserve fund from the Available to pay out funds.</p>
                            </div>
                            <div class="col-4 font-weight-bold text-right">
                                @if ($wallets['reserve']->balance < 0)
                                    <span class="text-danger">- {{ strtoupper($wallets['reserve']->currency) }} {{ getFormattedAmount($wallets['reserve']->currency, abs($wallets['reserve']->balance), false) }}</span>
                                @else
                                    {{ strtoupper($wallets['reserve']->currency) }} {{ getFormattedAmount($wallets['reserve']->currency, $wallets['reserve']->balance, false) }}
                                @endif
                            </div>
                        </div>
                    </div>
                </a>
                <a href="{{ route('dashboard.business.balance.wallet', [
                    $business->id,
                    $business->currency,
                    'deposit'
                ]) }}">
                    <div class="card-body border-top bg-light p-4">
                        <div class="row">
                            <div class="col-8">
                                <p class="mb-1">Deposit</p>
                                <p class="small text-secondary mb-0">Funds reserved to cover a negative balance on a HitPay account. Deposits are applied by HitPay for merchant accounts from high risk categories.</p>
                            </div>
                            <div class="col-4 font-weight-bold text-right">
                                @if ($wallets['deposit']->balance < 0)
                                    <span class="text-danger">- {{ strtoupper($wallets['deposit']->currency) }} {{ getFormattedAmount($wallets['deposit']->currency, abs($wallets['deposit']->balance), false) }}</span>
                                @else
                                    {{ strtoupper($wallets['deposit']->currency) }} {{ getFormattedAmount($wallets['deposit']->currency, $wallets['deposit']->balance, false) }}
                                @endif
                            </div>
                        </div>
                    </div>
                </a>
                <div class="card-body border-top pt-0 pb-4"></div>
            </div>
            @isset($stripeCustomAccountBalance)
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h3 class="font-weight-bold mb-3">Card payments</h3>
                        <p>Includes Card and AliPay transactions (Singapore)</p>
                        <p class="small">
                            <a href="{{ route('dashboard.business.balance.stripe.transactions', [ $business->id ]) }}">
                                View all transaction
                            </a>
                        </p>
                    </div>
                    @foreach ($stripeCustomAccountBalance as $currency)
                        @foreach ($currency as $balance)
                            @if ($balance['type'] === 'available')
                                <div class="card-body border-top bg-light p-4">
                                    <div class="row" style="font-size: 110%">
                                        <div class="col-8">
                                            <p class="font-weight-bold mb-0">Available to pay out</p>
                                        </div>
                                        <div class="col-4 font-weight-bold text-right">
                                            @if ($balance['amount'] < 0)
                                                <span class="text-danger">- {{ strtoupper($balance['currency']) }} {{ getFormattedAmount($balance['currency'], abs($balance['amount']), false) }}</span>
                                            @elseif ($balance['amount'] > 0)
                                                <span class="text-success">{{ strtoupper($balance['currency']) }} {{ getFormattedAmount($balance['currency'], $balance['amount'], false) }}</span>
                                            @else
                                                {{ strtoupper($balance['currency']) }} {{ getFormattedAmount($balance['currency'], $balance['amount'], false) }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @elseif ($balance['type'] === 'pending')
                                <div class="card-body border-top bg-light p-4">
                                    <div class="row" style="font-size: 110%">
                                        <div class="col-8">
                                            <p class="mb-0">Will be available soon</p>
                                        </div>
                                        <div class="col-4 font-weight-bold text-right">
                                            @if ($balance['amount'] < 0)
                                                <span class="text-danger">- {{ strtoupper($balance['currency']) }} {{ getFormattedAmount($balance['currency'], abs($balance['amount']), false) }}</span>
                                            @elseif ($balance['amount'] > 0)
                                                <span class="text-success">{{ strtoupper($balance['currency']) }} {{ getFormattedAmount($balance['currency'], $balance['amount'], false) }}</span>
                                            @else
                                                {{ strtoupper($balance['currency']) }} {{ getFormattedAmount($balance['currency'], $balance['amount'], false) }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                        <div class="card-body border-top pt-0 pb-4"></div>
                    @endforeach
                </div>
            @endisset
        </div>
        <business-help-guide :page_type="'hitpay_balance'"></business-help-guide>
    </div>
@endsection

@push('body-stack')
    <script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>
@endpush
