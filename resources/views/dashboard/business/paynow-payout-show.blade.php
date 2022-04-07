@extends('layouts.business', [
    'title' => 'HitPay Payouts for '.$transfer->created_at->toDateString(),
])

@section('business-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 mb-4">
            <a href="{{ route('dashboard.business.payment-provider.paynow.payout', [
                $business->getKey(),
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to HitPay Payouts</a>
        </div>
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card shadow-sm mb-3">
                <div class="card-body p-4">
                    <h2 class="text-primary mb-3 title">HitPay Payouts</h2>
                    <label class="small text-uppercase text-muted mb-3"># {{ $transfer->getKey() }}</label>
                    <p>
                    @switch ($transfer->status)
                        @case ('succeeded')
                        @case ('succeeded_manually')
                        <span class="badge badge-success badge-lg">Succeeded</span>
                        @break
                        @case ('request_pending')
                        <span class="badge badge-warning badge-lg">Pending</span>
                        @break
                        @default
                        <span class="badge badge-secondary badge-lg">{{ ucwords(str_replace('_', ' ', $transfer->status)) }}</span>
                    @endswitch
                    </p>
                    @php([
                        $bankSwiftCode,
                        $bankAccountNumber,
                    ] = explode('@', $transfer->payment_provider_account_id))
                    <p style="font-weight: bold">Receiver Details</p>
                    <p class="mb-1">Name : {{ $transfer->data['account']['name'] ?? $transfer->business->name }}</p>
                    @isset(\App\Business\Transfer::$availableBankSwiftCodes[$bankSwiftCode])
                        @php($bank = \App\Business\Transfer::$availableBankSwiftCodes[$bankSwiftCode].' ('.$bankSwiftCode.')')
                    @endisset
                    <p class="mb-1">Bank : {{ $bank ?? $bankSwiftCode }}</p>
                    <p class="mb-1">Account No : {{ $bankAccountNumber }}</p>
                    <br>
                    <p style="font-weight: bold">PayOut Details</p>
                    <p class="mb-1">HitPay Reference ID : {{ $transfer->id }}</p>
                    <p class="mb-1">Payout Date : {{ $transfer->created_at->toDateString() }}</p>
                    <p @if($transfer->payment_provider_transfer_method !== 'wallet_fast')class="mb-1"@endif>Net Payout Amount : {{ getFormattedAmount($transfer->currency, $transfer->amount) }}</p>
                    @if ($transfer->payment_provider_transfer_method === 'wallet_fast')
                        <p class="small mb-0"><a href="{{ route('dashboard.business.payment-provider.paynow.payout.download', [
                            'business_id' => $transfer->business_id,
                            'b_transfer' => $transfer->id,
                        ]) }}" target="_blank">View Payout Breakdown</a></p>
                    @else
                        <p>Related Charges : {{ $transfer->charges->count() }}</p>
                        <ol class="mb-0">
                            @foreach ($transfer->charges as $charge)
                                <li style="font-family: monospace">Charge ID: {{ $charge->id }} - {{ getFormattedAmount($charge->currency, $charge->amount) }}
                                    (Net: {{ getFormattedAmount($charge->home_currency, $charge->home_currency_amount - $charge->getTotalFee()) }})
                                    @if ($charge->target)
                                        @if ($charge->target instanceof \App\Business\RecurringBilling)
                                            <br>Recurring Plan, Bill Reference: {{ $charge->target->dbs_dda_reference }}
                                        @elseif  ($charge->target instanceof \App\Business\Order)
                                            <br>Order, Order ID: {{ $charge->target->id }}
                                        @endif
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
