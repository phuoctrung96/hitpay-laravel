@extends('hitpay-email.layouts.base', [
  'title' => 'Accept GrabPay and Paylater by Grab with HitPay',
  'preheader' => ''
])

@section('content')
  <p>Hello,</p>

  <p>Thank you for submitting your interest to accept GrabPay and PayLater by Grab using HitPay.</p>
  <p>If you already have an existing Grab Merchant ID, please perform the below 2 steps.</p>

  <p style="font-weight: bold; text-decoration: underline">Step 1</p>
  <p>Submit a request to Grab to close your existing GrabPay Merchant Account by clicking on the link below:</p>
  <a href="https://help.grab.com/merchant/en-sg/900002920386-%E2%9C%8D%EF%B8%8F-Id-like-to-close-my-GrabPay-Merchant-account">https://help.grab.com/merchant/en-sg/900002920386-%E2%9C%8D%EF%B8%8F-Id-like-to-close-my-GrabPay-Merchant-account</a>

  <p style="font-weight: bold; text-decoration: underline">Step 2</p>
  <p>Reply back to this email with a <b style="color: green">'YES'</b> and confirm your intention to start accepting GrabPay and PayLater by Grab with HitPay.</p>
  <p>Let us know if you have any questions.</p>

  <p>Team HitPay</p>
@endsection