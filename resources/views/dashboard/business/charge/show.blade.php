@php($customer_name = $charge->display('customer_name'))
@extends('layouts.business', [
    'title' => 'Charge for '.$customer_name,
])

@section('business-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 mb-4">
            <a href="{{ route('dashboard.business.charge.index', [
                $business->getKey(),
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to Sales & Report</a>
        </div>
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card shadow-sm mb-3">
                <div class="card-body p-4">
                    <label class="small text-uppercase text-muted mb-3">Charge # {{ $charge->getKey() }}</label>
                    <h2 class="text-primary mb-3 title">{{ $customer_name }}</h2>
                    <span class="float-right text-right">
                        {{ $charge->display('amount') }}
                        @if($charge->refunds->where('is_cashback',1)->count())
                            <br><small><span class="text-muted">
                                        Cashback:
                                </span> <span class="text-danger font-weight-bold">{{ $charge->display('cashback_amount') }}</span></small>
                        @endif
                        @if ($charge->refunds->where('is_cashback',0)->where('is_campaign_cashback',0)->count())
                            <br><small><span class="text-muted">
                                        Refunded:
                                </span> <span class="text-danger font-weight-bold">{{ $charge->display('refunded_amount') }}</span></small>
                        @endif
                    </span>
                    @if ($charge->remark)
                        <p class="text-dark mb-2">{{ $charge->remark }}</p>
                    @endif
                    <p class="small mb-0">Name: <span class="text-muted">{{ $charge->customer_name ?? '-' }}</span></p>
                    @if ($charge->display('shop_name'))
                        <p class="small mb-0">Shop Name: <span class="text-muted">{{ $charge->display('shop_name') ?? '-' }}</span></p>
                    @endif
                    <p class="small mb-0">Email: <span class="text-muted">{{ $charge->customer_email ?? '-' }}</span></p>
                    <p class="small mb-2">Phone Number: <span class="text-muted">{{ $charge->customer_phone_number ?? '-' }}</span></p>
                    <p class="text-dark small mb-0">Payment Method: <span class="text-muted">
                    {!! \App\Enumerations\Business\PaymentMethodType::displayName($charge->payment_provider_charge_method) !!}
                    </span></p>
                    @if($charge->payment_provider === \App\Enumerations\PaymentProvider::DBS_SINGAPORE && isset($charge->data['txnInfo']))
                        <p class="small mb-0">Payment Source: {{$charge->data['txnInfo']['senderParty']['senderBankId']}}</p>
                    @elseif($charge->payment_provider === \App\Enumerations\PaymentProvider::GRABPAY)
                        <p class="small mb-0">Payment Source: GrabPay Singapore</p>
                    @endif
                    @if ($charge->target && $charge->target instanceof \App\Business\RecurringBilling  && $charge->target->payment_provider === \App\Enumerations\PaymentProvider::DBS_SINGAPORE)
                        <p class="text-dark small mb-0">Bill Reference:: <span class="text-muted">{{ $charge->target->dbs_dda_reference }}</span></p>
                    @endif
                    @if (($card = $charge->card()) instanceof \HitPay\Data\Objects\PaymentMethods\Card)
                        @php($brand = $card->brand ? ucwords($card->brand) : 'Unknown')
                        <p class="text-dark small mb-0">Card: <span class="text-muted">{{ $brand }} (****{{ $card->last_4 }}, {{ $card->country_name }})</span></p>
                    @endif
                    <p class="text-dark small mb-0">Relatable: <span class="text-muted">
                        @if ($charge->target)
                            @switch (get_class($charge->target))
                                @case (\App\Business\Order::class)
                                    Order
                                    @break
                                @case (\App\Business\RecurringBilling::class)
                                    Recurring Plan
                                    @break
                            @endswitch
                        @else
                            None
                        @endif
                    </span></p>
                    @switch ($charge->status)
                        @case ('succeeded')
                            <p class="text-dark small mb-0">All Inclusive Fee:
                                <span class="text-muted">{{ $charge->display('all_inclusive_fee') }}</span></p>
                            <p class="text-dark small mb-0">Collected at {{ $charge->closed_at->format('h:ia \o\n F d, Y (l)') }}</p>
                            @if($charge->refunds->where('is_campaign_cashback',1)->count())
                                <span class="badge badge-success">Succeeded (Campaign cashback)</span>
                            @elseif($charge->refunds->where('is_cashback',1)->count())
                                <span class="badge badge-success">Succeeded (with cashback)</span>
                            @elseif ($charge->amount - ($charge->balance ?? 0) !== $charge->amount)
                                <span class="badge badge-success">Partially Refunded</span>
                            @else
                                <span class="badge badge-success">Succeeded</span>
                            @endif
                            @break
                        @case ('refunded')
                            <span class="badge badge-warning">Refunded</span>
                            @break
                        @case ('requires_payment_method')
                            <span class="badge badge-info">Payment In Progress</span>
                            @break
                        @case ('requires_customer_action')
                            <span class="badge badge-info">Waiting For Customer</span>
                            @break
                        @case ('failed')
                            <span class="badge badge-danger">Failed</span>
                            @break
                        @case ('canceled')
                        @case ('expired')
                            <span class="badge badge-secondary">Expired</span>
                            @break
                        @default
                            <span class="badge badge-secondary">{{ $charge->status }}</span>
                    @endswitch

                </div>
                @if ($charge->refunds->where('is_campaign_cashback', 0)->count())
                    <div class="card-body px-4 border-top">
                        <ol class="small mb-0">
                            @foreach ($charge->refunds->where('is_campaign_cashback', 0) as $refund)
                                <li><span class="font-weight-bold">{{ getFormattedAmount($charge->currency, $refund->amount) }}</span> <span class="text-muted">
                                        @if($refund->is_cashback)
                                            cashback was made at
                                        @else
                                            refunded at
                                        @endif
                                        {{ $refund->created_at->toDateTimeString() }}.</span><br><small class="text-monospace">Reference ID: {{ $refund->id }}</small></li>
                            @endforeach
                        </ol>
                    </div>
                @endif
                @if ($charge->status === 'succeeded')
                    <div class="card-footer px-4 border-top">
                        @if ($charge->payment_provider !== \App\Enumerations\PaymentProvider::DBS_SINGAPORE)
                            <business-refund></business-refund>
                        @else
                            <business-paynow-refund></business-paynow-refund>
                        @endif
                        <business-charge></business-charge>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('body-stack')
    <script type="text/javascript">
        window.Business = @json($business);
        window.Charge = @json($charge);
    </script>
    <script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>
@endpush
