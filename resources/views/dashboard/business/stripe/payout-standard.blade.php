@php($title = __('Stripe Payouts'))
@php($type='stripe')
@extends('layouts.business')

@section('business-content')
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-8 main-content">
            @if ($provider)
                <div class="btn-group btn-group-sm mb-3 w-100 shadow-sm">
                    <a class="btn col text-uppercase d-flex {{ $type === 'paynow' ? 'active btn-outline-primary' : 'btn-light' }}"
                       href="{{ route('dashboard.business.payment-provider.paynow.payout', [$business->getKey()]) }}">
                        <span class="w-100 align-self-center">HitPay Payouts</span>
                    </a>

                    @if(
                        $business->payment_provider === \App\Enumerations\PaymentProvider::STRIPE_SINGAPORE &&
                        $provider->payment_provider_account_type === 'standard'
                    )
                        <a class="btn col text-uppercase d-flex {{ $type === 'stripe' ? 'active btn-outline-primary' : 'bg-light' }}"
                           href="{{ route('dashboard.business.payment-provider.stripe.payout.standard', [$business->getKey()]) }}">
                            <span class="w-100 align-self-center">Stripe Payouts</span>
                        </a>
                    @endif

                    @if(
                        $business->payment_provider === \App\Enumerations\PaymentProvider::STRIPE_SINGAPORE &&
                        $provider->payment_provider_account_type === 'custom'
                    )
                        <a class="btn col text-uppercase d-flex {{ $type === 'stripe' ? 'active btn-outline-primary' : 'bg-light' }}"
                           href="{{ route('dashboard.business.payment-provider.stripe.payout.custom', [$business->getKey()]) }}">
                            <span class="w-100 align-self-center">Cards Payouts</span>
                        </a>
                    @endif

                    <a class="btn col text-uppercase d-flex {{ $type === 'platform' ? 'active btn-outline-primary' : 'bg-light' }}"
                       href="{{ route('dashboard.business.platform.payout', [$business->getKey()]) }}">
                        <span class="w-100 align-self-center">Platform Payouts</span>
                    </a>
                </div>
            @endif
            <div class="card border-0 shadow-sm mb-3">
                @if($type ==='stripe')
                    <div class="card-body p-4">
                        <h2 class="text-primary mb-3 title">Stripe Payouts</h2>
                        <p class="mb-0">View and edit your linked bank account at
                            <a href="https://dashboard.stripe.com/account/payouts">Stripe</a>
                        </p>
                        <p class="small mb-0">(Click
                            <a href="https://dashboard.stripe.com/account/payouts">here</a>
                            or copy link
                            <a href="https://dashboard.stripe.com/account/payouts">https://dashboard.stripe.com/account/payouts</a>
                            )
                        </p>
                    </div>
                @endif
                <div class="card-body border-top px-4 py-2">
                    <p class="small text-muted mb-0">Showing the latest {{ $data->count() }} results</p>
                </div>
                @if ($data->count())
                    @foreach ($data as $payout)
                        <div class="card-body bg-light border-top p-4">
                            <span class="float-right">{{ $payout['amount'] }}</span>
                            <p class="font-weight-bold mb-2">{{ $payout['description'] }}</p>
                            <p class="text-dark small mb-2"><span class="text-muted"># {{ $payout['id'] }}</span></p>
                            <p class="text-dark small mb-0">Arrival Date:
                                <span class="text-muted">{{ $payout['arrival_date'] }}</span></p>
                            <p class="text-dark small mb-2">Payout Destination:
                                <span class="text-muted">{{ ucwords(str_replace('_', ' ', $payout['type'])) }}</span>
                            </p>
                            @if ($payout['status'] === 'in_transit')
                                <span class="small font-weight-bold text-primary">In Transit</span>
                            @elseif ($payout['status'] === 'paid')
                                <span class="small font-weight-bold text-success">Paid</span>
                            @elseif ($payout['status'] === 'pending')
                                <span class="small font-weight-bold text-info">Pending</span>
                            @else
                                <span class="small font-weight-bold text-secondary">Unknown</span>
                            @endif
                        </div>
                    @endforeach
                @else
                    <div class="card-body bg-light border-top p-4">
                        <div class="text-center text-muted py-4">
                            <p><i class="fas fa-money-check-alt fa-4x"></i></p>
                            <p class="small mb-0">- No payout found -</p>
                        </div>
                    </div>
                @endif
                <div class="card-body border-top py-2">
                    <p class="small text-muted mb-0">View and edit your linked bank account at
                        <a href="https://dashboard.stripe.com/account/payouts">Stripe</a>
                        .
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
