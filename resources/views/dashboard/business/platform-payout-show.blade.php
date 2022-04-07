@extends('layouts.business', [
    'title' => 'Commission Payout for '.$commission->created_at->toDateString(),
])

@section('business-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 mb-4">
            <a href="{{ route('dashboard.business.platform.payout', [
                $business->getKey(),
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to Commission Payouts</a>
        </div>
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card shadow-sm mb-3">
                <div class="card-body p-4">
                    <h2 class="text-primary mb-3 title">Commission Payout</h2>
                    <label class="small text-uppercase text-muted mb-3"># {{ $commission->getKey() }}</label>
                    <p>
                    @switch ($commission->status)
                        @case ('succeeded')
                        @case ('succeeded_manually')
                        <span class="badge badge-success badge-lg">Succeeded</span>
                        @break
                        @case ('request_pending')
                        <span class="badge badge-warning badge-lg">Pending</span>
                        @break
                        @default
                        <span class="badge badge-secondary badge-lg">{{ ucwords(str_replace('_', ' ', $commission->status)) }}</span>
                    @endswitch
                    </p>
                    @php([
                        $bankSwiftCode,
                        $bankAccountNumber,
                    ] = explode('@', $commission->payment_provider_account_id))
                    <p style="font-weight: bold">Receiver Details</p>
                    <p class="mb-1">Name : {{ $commission->data['account']['name'] ?? $commission->business->name }}</p>
                    @isset(\App\Business\Transfer::$availableBankSwiftCodes[$bankSwiftCode])
                        @php($bank = \App\Business\Transfer::$availableBankSwiftCodes[$bankSwiftCode].' ('.$bankSwiftCode.')')
                    @endisset
                    <p class="mb-1">Bank : {{ $bank ?? $bankSwiftCode }}</p>
                    <p class="mb-1">Account No : {{ $bankAccountNumber }}</p>
                    <br>
                    <p style="font-weight: bold">PayOut Details</p>
                    <p class="mb-1">HitPay Reference ID : {{ $commission->id }}</p>
                    <p class="mb-1">Payout Date : {{ $commission->created_at->toDateString() }}</p>
                    <p class="mb-1">Total Sales : {{ getFormattedAmount($commission->currency, $commission->charges->sum('amount')) }}</p>
                    <p class="mb-1">Net Payout Amount : {{ getFormattedAmount($commission->currency, $commission->amount) }}</p>
                    <p>Related Charges : {{ $commission->charges->count() }}</p>
                    <ol class="mb-0">
                        @foreach ($commission->charges as $charge)
                            <li style="font-family: monospace">Charge ID: {{ $charge->id }} - {{ getFormattedAmount($charge->currency, $charge->amount) }}<br>(Commission: {{ getFormattedAmount($charge->home_currency, $charge->getCommission()) }}, REF: {{ $charge->plugin_provider_reference }})</li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection
