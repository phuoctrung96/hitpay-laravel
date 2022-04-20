@php($title = __('HitPay Payouts'))
@php($type='stripe')
@extends('layouts.business')

@section('business-content')
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-8 main-content">
            @if ($provider)
                <div class="btn-group btn-group-sm mb-3 w-100 shadow-sm">
                    <a
                        class="btn col text-uppercase d-flex
                        {{ $type === 'stripe' ? 'active btn-outline-primary' : 'btn-light' }}"
                        href="{{ route('dashboard.business.payouts.index', [$business->getKey()]) }}">
                        <span class="w-100 align-self-center">HitPay Payouts</span>
                    </a>

                    @if(
                        $business->payment_provider === \App\Enumerations\PaymentProvider::STRIPE_SINGAPORE &&
                        $provider->payment_provider_account_type === 'standard'
                    )
                        <a
                            class="btn col text-uppercase d-flex
                            {{ $type === 'stripe' ? 'active btn-outline-primary' : 'bg-light' }}"
                            href="{{ route('dashboard.business.payment-provider.stripe.payout.standard', [$business->getKey()]) }}">
                            <span class="w-100 align-self-center">Stripe Payouts</span>
                        </a>
                    @endif

                    @if(
                        $business->payment_provider === \App\Enumerations\PaymentProvider::STRIPE_SINGAPORE &&
                        $provider->payment_provider_account_type === 'custom'
                    )
                        <a
                            class="btn col text-uppercase d-flex
                            {{ $type === 'stripe' ? 'active btn-outline-primary' : 'bg-light' }}"
                            href="{{ route('dashboard.business.payment-provider.stripe.payout.custom', [$business->getKey()]) }}">
                            <span class="w-100 align-self-center">Cards Payouts</span>
                        </a>
                    @endif

                    <a
                        class="btn col text-uppercase d-flex {{ $type === 'platform' ? 'active btn-outline-primary' : 'bg-light' }}"
                        href="{{ route('dashboard.business.platform.payout', [$business->getKey(),]) }}">
                        <span class="w-100 align-self-center">Platform Payouts</span>
                    </a>
                </div>
            @endif
            <div class="card border-0 shadow-sm mb-3">
                @if($type ==='stripe')
                    <div class="card-body p-4">
                        <h2 class="text-primary mb-3 title">HitPay Payouts</h2>
                    </div>
                @endif
                <div class="card-body border-top px-4 py-2">
                    <p class="small text-muted mb-0">Showing the latest {{ $paginator->count() }} results</p>
                </div>
                @if ($paginator->count())
                    @foreach ($paginator as $payout)
                        <a class="hoverable" href="#">
                            <div class="card-body bg-light border-top p-4">
                                <span class="float-right">{{ getFormattedAmount($payout->currency, $payout->amount) }}</span>
                                <p class="font-weight-bold mb-2">{{ $payout->remark }}</p>
                                <p class="text-dark small mb-2"><span class="text-muted"># {{ $payout->id }}</span></p>
                                @if ($payout['status'] === 'succeeded' || $payout['status'] === 'succeeded_manually')
                                    <p class="text-dark small mb-0">Transferred Date:
                                        <span class="text-muted">{{ $payout['updated_at'] }}</span></p>
                                @endif
                                <p class="text-dark small mb-2">Payout Destination:
                                    <span class="text-muted">{{ $payout->payment_provider_account_id }}</span>
                                </p>
                                @if (isset($payout->data['file']))
                                    <div class="mb-2">
                                        <a class="btn btn-sm btn-primary" target="_blank" href="{{ route('dashboard.business.payment-provider.stripe.payout.custom.download', [
                                            $business->getKey(),
                                            $payout->getKey(),
                                        ]) }}">Click here to download</a>
                                    </div>
                                @endif
                                @if(isset($payout->data['stripe']['payout']['arrival_date']) && is_int($payout->data['stripe']['payout']['arrival_date']))
                                    @php($estimatedArrivalDate = \Illuminate\Support\Facades\Date::createFromTimestamp($payout->data['stripe']['payout']['arrival_date'])->toDateString())
                                @endif
                                @if ($payout['status'] === 'succeeded')
                                    <span class="small font-weight-bold text-success">Paid</span>
                                @elseif ($payout['status'] === 'paid')
                                    <span class="small font-weight-bold text-success">Paid</span>
                                    @isset($estimatedArrivalDate)
                                        <span class="small text-muted">(Estimated Arrival: {{ $estimatedArrivalDate }})</span>
                                    @endisset
                                @elseif ($payout['status'] === 'in_transit')
                                    <span class="small font-weight-bold text-info">In Transit</span>
                                    @isset($estimatedArrivalDate)
                                        <span class="small text-muted">(Estimated Arrival: {{ $estimatedArrivalDate }})</span>
                                    @endisset
                                @elseif ($payout['status'] === 'succeeded_manually')
                                    <span class="small font-weight-bold text-info">Paid</span>
                                @else
                                    <span class="small font-weight-bold text-warning">Pending</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                @else
                    <div class="card-body bg-light border-top p-4">
                        <div class="text-center text-muted py-4">
                            <p><i class="fas fa-money-check-alt fa-4x"></i></p>
                            <p class="small mb-0">- No payout found -</p>
                        </div>
                    </div>
                @endif
                <div class="card-body border-top pt-2">
                </div>
            </div>

            @include('custom-pagination')
        </div>
    </div>
@endsection
