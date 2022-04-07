@extends('hitpay-email.layouts.base', [
    'title' => $title,
    'preheader' => $title,
])

@section('content')
    <p><img src="{{ asset('hitpay/logo-000036.png') }}" width="160" alt="HitPay"></p>
    <p>Dear User,</p>
    
    <p>Your HitPay account verification has been approved.&nbsp;</p>
    <p>You can start accepting payments using <a href="https://hitpay.zendesk.com/hc/en-us/articles/900005115766-What-are-the-plugins-platforms-supported-by-HitPay-">e-commerce website plugins</a> or HitPay&rsquo;s <a href="https://hitpay.zendesk.com/hc/en-us/articles/4486752783641-How-can-I-start-selling-online-without-a-website-">no-code commerce tools</a> if you do not have a website.&nbsp;</p>
    <p>Let us know if you have any questions.</p>
    
    <p>Team HitPay</p>
@endsection
