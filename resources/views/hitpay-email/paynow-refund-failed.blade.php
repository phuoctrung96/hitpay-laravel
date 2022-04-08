@extends('hitpay-email.layouts.base', [
    'title' => $title,
    'preheader' => $title,
])

@section('content')
    <p><img src="{{ asset('hitpay/logo-000036.png') }}" width="160" alt="HitPay"></p>
    <p>Dear {{ $charge->business->name }}!</p>
    <p>Please note that your recently attempted refund has failed due to the recipient not being a valid PayNow user. Please contact HitPay on WhatsApp at +65 98644718.</p>
    <br>
    <p style="font-weight: bold">Details</p>
    <p style="font-family: monospace">Charge ID&nbsp;&nbsp;&nbsp;&nbsp; : {{ $charge->id }}</p>
    <p style="font-family: monospace">Reference ID&nbsp; : {{ $reference_id }}</p>
    <p style="font-family: monospace">Refund Amount&nbsp;: {{ $amount }}</p>
    <br>
    <p>Best,</p>
    <p>Team HitPay</p>
@endsection
