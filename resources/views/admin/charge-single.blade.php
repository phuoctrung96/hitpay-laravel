@extends('layouts.admin', [
    'title' => 'Charges'
])

@section('admin-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 mb-4">
            <a href="{{ route('admin.charge.index', [
                'status' => $charge->status,
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to Charges</a>
        </div>
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card shadow-sm mb-3">
                <div class="card-body p-4">
                    <div>
                        <label class="small text-uppercase text-muted mb-3">Charge # {{ $charge->getKey() }}</label>
                    </div>
                    <span class="float-right">
                        {{ $charge->display('amount') }}
                        @if ($charge->amount - ($charge->balance ?? 0) !== $charge->amount)
                            <br><span class="small">Balance: {{ $charge->display('balance') }}</span>
                        @endif
                    </span>
                    @if ($charge->remark)
                        <p class="text-dark mb-2">{{ $charge->remark }}</p>
                    @endif
                    <p class="small mb-0">Name: <span class="text-muted">{{ $charge->customer_name ?? '-' }}</span></p>
                    <p class="small mb-0">Email: <span class="text-muted">{{ $charge->customer_email ?? '-' }}</span></p>
                    <p class="small mb-2">Phone Number: <span class="text-muted">{{ $charge->customer_phone_number ?? '-' }}</span></p>
                    <p class="small mb-0">Channel: <span class="text-muted">{{ ucwords(str_replace('_', ' ', $charge->channel)) }}</span></p>
                    <p class="small mb-0">Business: <a href="{{ route('admin.business.show', [
                        'business_id' => $charge->business_id,
                    ]) }}">{{ $charge->business->name }}</a></p>
                    <p class="small mb-0">Platform Reference ID: <span class="text-muted">{{ ucwords(str_replace('_', ' ', $charge->payment_provider)) }} <span class="font-weight-bold">{{ $charge->payment_provider_charge_id }}</span></span></p>
                    @if ($charge->channel === \App\Enumerations\Business\Channel::PAYMENT_GATEWAY)
                        <p class="small mb-0">Plugin: <span class="text-muted">{{ ucwords(str_replace('_', ' ', $charge->plugin_provider)) }}</span></p>
                        <p class="small mb-0">Reference: <span class="text-muted">{{ $charge->plugin_provider_reference }}</span></p>
                        @if ($charge->plugin_provider_order_id)
                            <p class="small mb-0">Order ID: <span class="text-muted">{{ $charge->plugin_provider_order_id }}</span></p>
                        @endif
                        <p class="small mb-0">Callback Status: <span class="text-muted">{{ $charge->is_successful_plugin_callback ? 'Succeeded' : 'Failed' }}</span></p>
                    @endif
                    <p class="text-dark small mb-0">Payment Method: <span class="text-muted">
                    {!! \App\Enumerations\Business\PaymentMethodType::displayName($charge->payment_provider_charge_method) !!}
                    </span></p>
                    @if($charge->payment_provider === \App\Enumerations\PaymentProvider::DBS_SINGAPORE && isset($charge->data['txnInfo']))
                        <p class="small mb-0">Payment Source: {{$charge->data['txnInfo']['senderParty']['senderBankId']}}</p>
                    @elseif($charge->payment_provider === \App\Enumerations\PaymentProvider::GRABPAY)
                        <p class="small mb-0">Payment Source: GrabPay Singapore</p>
                    @endif
                    @if(in_array($charge->payment_provider, [
                        \App\Enumerations\PaymentProvider::STRIPE_MALAYSIA,
                        \App\Enumerations\PaymentProvider::STRIPE_SINGAPORE,
                        \App\Enumerations\PaymentProvider::STRIPE_US,
                    ]) && ($card = $charge->card()) instanceof \HitPay\Data\Objects\PaymentMethods\Card)
                        Card Issuer: <span class="text-muted">{{ $card->issuer ?: '-' }}</span>
                        <p class="text-dark small mb-0">Card Brand: <span class="text-muted">{{ $card->brand_name }}</span></p>
                        <p class="text-dark small mb-0">Card Last 4: <span class="text-muted">{{ $card->last_4 }}</span></p>
                        @php($paymentMethodDetails = $charge->data['stripe']['charge']['payment_method_details'] ?? $charge->data['payment_method_details'])
                        @php($cardData = $paymentMethodDetails['card'] ?? $paymentMethodDetails['card_present'])
                        @if (isset($cardData['wallet']))
                            <p class="text-dark small mb-0">Wallet Type: <span class="text-muted">{{ $cardData['wallet']['type'] }}</span></p>
                        @endif
                        <p class="text-dark small mb-0">Card Country: <span class="text-muted">{{ $card->country_name }}</span></p>
                        <p class="text-dark small mb-0">Card Funding: <span class="text-muted">{{ $card->funding }}</span></p>
                        <p class="text-dark small mb-0">Card Network: <span class="text-muted">{{ $card->network }}</span></p>
                        <p class="text-dark small mb-0">Card Expiry Year: <span class="text-muted">{{ $card->exp_year }}</span></p>
                        <p class="text-dark small mb-0">Card Expiry Month: <span class="text-muted">{{ $card->exp_month }}</span></p>
                    @elseif($charge->payment_provider == \App\Enumerations\PaymentProvider::DBS_SINGAPORE && isset($charge->data['txnInfo']))
                        <p class="text-dark small mb-0">Sender Party Name: <span class="text-muted">{{$charge->data['txnInfo']['senderParty']['name']}}</span></p>
                        <p class="text-dark small mb-0">Sender Party Bank Id: <span class="text-muted">{{$charge->data['txnInfo']['senderParty']['senderBankId']}}</span></p>
                    @endif
                    @switch ($charge->status)
                        @case ('succeeded')
                            <p class="text-dark small mb-0">All Inclusive Fee: <span class="text-muted">{{ $charge->display('all_inclusive_fee') }}{{ ($originalFee = $charge->display('all_inclusive_fee_original_currency')) ? ' ('.$originalFee.')' : '' }}</span></p>
                            <p class="text-dark small mb-0">Collected at {{ $charge->closed_at->format('h:ia \o\n F d, Y (l)') }}</p>
                            @if($charge->refunds->where('is_campaign_cashback',1)->count())
                                <span class="badge badge-success">Succeeded (Campaign cashback)</span>
                            @elseif($charge->refunds->where('is_cashback',1)->count())
                                <span class="badge badge-success">Succeeded (with cashback)</span>
                            @elseif ($charge->amount - ($charge->balance ?? 0) !== $charge->amount)
                                <span class="badge badge-info">Partially Refunded</span>
                            @endif
                            @break
                        @case ('refunded')
                            <span class="badge badge-warning">Refunded</span>
                            <p class="text-dark small mb-0">Created at {{ $charge->created_at->format('h:ia \o\n F d, Y (l)') }}</p>
                            <p class="text-dark small mb-0">Fully refunded at {{ $charge->closed_at->format('h:ia \o\n F d, Y (l)') }}</p>
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
                    @if ($charge->target)
                        @switch (get_class($charge->target))
                            @case (\App\Business\Order::class)
                                <span class="badge badge-primary">Order</span>
                                @break
                            @case (\App\Business\RecurringBilling::class)
                                <span class="badge badge-info">Recurring Plan</span>
                                @break
                        @endswitch
                    @endif

                    @if ($charge->refunds->count())
                        <p class="small mb-0">Refunded Records:</p>
                        <ol class="small mb-0">
                            @foreach ($charge->refunds as $refund)
                                <li><span class="font-weight-bold">{{ getFormattedAmount($charge->currency, $refund->amount) }}</span> <span class="text-muted">refunded at {{ $refund->created_at->toDateTimeString() }}.</span><br><small class="text-monospace">Reference ID: {{ $refund->id }}</small></li>
                            @endforeach
                        </ol>
                    @endif
                </div>
                <form class="card-body border-top" action="{{ route('admin.charge.notify.source', $charge->getKey()) }}" method="post">
                    @csrf
                    <div class="form-group mb-0">
                        <button type="submit" class="btn btn-danger">Notify as non-identifiable source</button>
                    </div>
                </form>
                @if ($successMessage = session('success_message'))
                    <div class="alert alert-success border-left-0 border-right-0 rounded-0 mb-0">
                        {{ $successMessage }}
                    </div>
                    @if ($charge->status !== 'succeeded')
                        <div class="card-body"></div>
                    @endif
                @endif
                @if ($charge->status === 'succeeded')
                    <form class="card-body border-top" action="{{ route('admin.charge.refund', $charge->getKey()) }}" method="post">
                        @method('put')
                        @csrf
                        <div class="form-group">
                            <label for="amount" class="small text-secondary">Amount</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">{{ strtoupper($charge->currency) }}</span>
                                </div>
                                <input id="amount" name="amount" class="form-control{{ $errors->has('amount') ? ' is-invalid' : '' }}" autocomplete="off" value="{{ old('amount', $amount ?? null) }}" autofocus>
                            </div>
                            @error('amount')
                                <span class="small text-danger mt-1" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-success">Refund</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection
