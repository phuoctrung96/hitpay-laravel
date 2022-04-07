@extends('hitpay-email.layouts.base', [
    'title' => $title,
    'preheader' => $title,
])

@section('content')
    <p><img src="{{ asset('hitpay/logo-000036.png') }}" width="160" alt="HitPay"></p>
    <p>Dear User,</p>
    <p>This email is to notify you that your Xero account has been disconnected from HitPay due to an expired token.</p>
    <p>
        Please login to HitPay dashboard at {{$loginUrl}}, navigate to Settings > <a href="{{$xeroIntegrationUrl}}">Xero Integration</a>
        to connect your Xero organisation to HitPay.
    </p>
    <p>Please contact us at <a href="mailto:support@hit-pay.com">support@hit-pay.com</a> if you have any questions.</p>
    <p>Best,</p>
    <p>Team HitPay</p>
@endsection
