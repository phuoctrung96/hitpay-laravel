@extends('hitpay-email.layouts.base', [
    'title' => $title,
    'preheader' => $title,
])

@section('content')
    <p><img src="{{ asset('hitpay/logo-000036.png') }}" width="160" alt="HitPay"></p>
    <p>Hi {{ $commission->business->name }}!</p>
    <p>Thank you for choosing HitPay as your payment acceptance partner for your business. This email is to inform you that a commission payout has been transferred into your bank account. Please find the following for the payout details.</p>
    <br>
    @php([
        $bankSwiftCode,
        $bankAccountNumber,
    ] = explode('@', $commission->payment_provider_account_id))
    <p style="font-weight: bold">Receiver Details</p>
    <p style="font-family: monospace">Name&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : {{ $commission->data['account']['name'] ?? $commission->business->name }}</p>
    @isset(\App\Business\Transfer::$availableBankSwiftCodes[$bankSwiftCode])
        @php($bank = \App\Business\Transfer::$availableBankSwiftCodes[$bankSwiftCode].' ('.$bankSwiftCode.')')
    @endisset
    <p style="font-family: monospace">Bank&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : {{ $bank ?? $bankSwiftCode }}</p>
    <p style="font-family: monospace">Account No : {{ $bankAccountNumber }}</p>
    <br>
    <p style="font-weight: bold">PayOut Details</p>
    <p style="font-family: monospace">HitPay Reference ID&nbsp; : {{ $commission->id }}</p>
    <p style="font-family: monospace">Payout Date&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : {{ $commission->created_at->toDateString() }}</p>
    <p style="font-family: monospace">Total Sales&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : {{ getFormattedAmount($commission->currency, $commission->charges->sum('amount')) }}</p>
    <p style="font-family: monospace">Net Payout Amount&nbsp;&nbsp;&nbsp; : {{ getFormattedAmount($commission->currency, $commission->amount) }}</p>
    <p style="font-family: monospace">Related Charges&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : {{ $commission->charges->count() }}</p>
    <ol>
        @foreach ($commission->charges as $charge)
            <li style="font-family: monospace">Charge ID: {{ $charge->id }} - {{ getFormattedAmount($charge->currency, $charge->amount) }} (Commission: {{ getFormattedAmount($charge->home_currency, $charge->getCommission()) }}, REF: {{ $charge->plugin_provider_reference }})</li>
        @endforeach
    </ol>
    <br>
    <p>If you have any questions, please contact us on WhatsApp at +65 98644718</p>
    <p>Best,</p>
    <p>Team HitPay</p>
@endsection
