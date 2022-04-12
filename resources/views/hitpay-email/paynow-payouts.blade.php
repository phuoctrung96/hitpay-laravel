@extends('hitpay-email.layouts.base', [
    'title' => $title,
    'preheader' => $title,
])

@section('content')
    <p><img src="{{ asset('hitpay/logo-000036.png') }}" width="160" alt="HitPay"></p>
    <p>Hi {{ $transfer->business->name }}!</p>
    <p>Thank you for choosing HitPay as your payment acceptance partner for your business. This email is to inform you that a HitPay Payouts has been transferred into your bank account. Please find the following for the payout details.</p>
    <br>
    @php([
        $bankSwiftCode,
        $bankAccountNumber,
    ] = explode('@', $transfer->payment_provider_account_id))
    <p style="font-weight: bold">Receiver Details</p>
    <p style="font-family: monospace">Name&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : {{ $transfer->data['account']['name'] ?? $transfer->business->name }}</p>
    @isset(\App\Business\Transfer::$availableBankSwiftCodes[$bankSwiftCode])
        @php($bank = \App\Business\Transfer::$availableBankSwiftCodes[$bankSwiftCode].' ('.$bankSwiftCode.')')
    @endisset
    <p style="font-family: monospace">Bank&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : {{ $bank ?? $bankSwiftCode }}</p>
    <p style="font-family: monospace">Account No : {{ $bankAccountNumber }}</p>
    <br>
    <p style="font-weight: bold">PayOut Details</p>
    <p style="font-family: monospace">HitPay Reference ID&nbsp; : {{ $transfer->id }}</p>
    <p style="font-family: monospace">Payout Date&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : {{ $transfer->created_at->toDateString() }}</p>
    <p style="font-family: monospace">Net Payout Amount&nbsp;&nbsp;&nbsp; : {{ getFormattedAmount($transfer->currency, $transfer->amount) }}</p>
    <br>
    <p>Login to HitPay Dashboard and navigate to Sales and Reports > Bank Payouts to view the breakdown of payout.</p>
    <p>If you have any questions, please contact us on WhatsApp at +65 89518262</p>
    <p>Best,</p>
    <p>Team HitPay</p>
@endsection
