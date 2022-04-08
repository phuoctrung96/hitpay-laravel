@extends('hitpay-email.layouts.base', [
    'title' => $title,
    'preheader' => $title,
])

@section('content')
    <p><img src="{{ asset('hitpay/logo-000036.png') }}" width="160" alt="HitPay"></p>
    <p>{{$business->getName()}} wants you to start saving on payment processing fees by over 30% and increase revenues!</p>
    <p>On HitPay, you can grow your business with the widest coverage of payment methods on online and POS sales channels that helps you increase sales and lower costs.</p>
    <p>HitPay also allows you to automate your business operations with e-commerce and accounting integrations.</p>
    <p>No setup or monthly fees. Only pay per transaction. HitPay's fee schedule can be found here</p>
    <p>Click on the link below to create your account with HitPay</p>
    <p><a href="{{route('register', ['referral_code' => $business->businessReferral->code])}}">Accept invitation</a></p>
    <br>
    <p>Best,</p>
    <p>Team HitPay</p>
@endsection
