@extends('hitpay-email.layouts.base', [
    'title' => $title,
    'preheader' => $title,
])

@section('content')
    <p><img src="{{ asset('hitpay/logo-000036.png') }}" width="160" alt="HitPay"></p>
    <p>Hello User,</p>

    <p>Your HitPay account verification has been rejected due to insufficient information provided.</p>

    <p>If you are signing up as a company, please ensure you complete verification as a company under settings &gt; verification and your bank account details belong to your company.</p>

    <p>If you are signing up as an individual, please ensure you upload sufficient information to prove that you are using HitPay only for selling of goods and services under settings &gt; verification. Accepted evidence includes customer invoices and business license.</p>

    <p>If you have any follow up questions , please send an email to <a href="mailto:compliance@hit-pay.com">compliance@hit-pay.com</a>.</p>

    <p>Thank you</p>
@endsection
