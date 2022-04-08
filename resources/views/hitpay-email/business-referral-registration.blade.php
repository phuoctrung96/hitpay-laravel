@extends('hitpay-email.layouts.base', [
    'title' => $title,
    'preheader' => $title,
])

@section('content')
    <p><img src="{{ asset('hitpay/logo-000036.png') }}" width="160" alt="HitPay"></p>
    <p>{{$referralBusiness->getName()}} is now a HitPay User</p>
    <p>Start earning referral fees of 0.1% for every transaction processed by your referral on HitPay.</p>
    <p>View fees for all your referrals under Refer and Earn in the HitPay Dashboard</p>
    <br>
    <p>Best,</p>
    <p>Team HitPay</p>
@endsection
