@extends('hitpay-email.layouts.base', [
    'title' => $title,
    'preheader' => $title,
])

@section('content')
    <p><img src="{{ asset('hitpay/logo-000036.png') }}" width="160" alt="HitPay"></p>
    <br>
    <h2 style="color: #222222; font-family: sans-serif; font-weight: 200; line-height: 1.4; margin: 0; Margin-bottom: 30px; font-size: 20px; text-transform: capitalize; text-align: center;">
        Share HitPay with other small business owners
    </h2>
    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">
        Refer HitPay using your unique referral URL below with other business owners and earn 0.1% in referral commission for every transaction processed by your referral using HitPay.
        <br>
        <br>
        <a href="{{route('register', ['referral' => $business->businessReferral->code])}}">{{route('register', ['referral' => $business->businessReferral->code])}}</a>
    </p>
    <br>
    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">
        For further information on HitPat's referral program, please refer to the link below
        <a href="https://hitpay.zendesk.com/hc/en-us/articles/4409709712537-How-do-I-refer-users-to-HitPay-">https://hitpay.zendesk.com/hc/en-us/articles/4409709712537-How-do-I-refer-users-to-HitPay-</a>
    </p>
    <br>
    <p>Team HitPay</p>
@endsection
