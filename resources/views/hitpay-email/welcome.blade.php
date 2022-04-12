@extends('hitpay-email.layouts.base', [
    'title' => $title,
    'preheader' => $title,
])

@section('content')
    <p><img src="{{ asset('hitpay/logo-000036.png') }}" width="160" alt="HitPay"></p>
    <p>Hi There!</p>
    <p>Thank you for choosing HitPay as your payment acceptance partner for your business.</p>
    <p>HitPay is your one-stop payments and commerce platform that allows you to sell on any sales channel. You can use HitPay to:</p>
    <ul>
        <li>Sell in-store or at pop-ups using HitPay Terminal or HitPay App</li>
        <li>Accept payments in your existing e-commerce platform or online web store</li>
        <li>Increase conversions on Instagram with one-click Instagram Shopping Checkouts</li>
        <li>Send one-off invoice or payment links</li>
        <li>Start a subscription or recurring payments business</li>
    </ul>
    <p>You can get started by logging on to our web dashboard at <a href="https://dashboard.hit-pay.com">https://dashboard.hit-pay.com</a> or by downloading the HitPay app from the iTunes Store or Google Play Store.</p>
    <p>You can find extensive resources on all our solutions at our online help centre : <a href="https://hitpay.zendesk.com">https://hitpay.zendesk.com</a></p>
    <p>Contact us on WhatsApp at +65 89518262</p>
    <p>Best,</p>
    <p>Team HitPay</p>
@endsection
