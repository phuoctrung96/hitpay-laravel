
@extends('hitpay-email.layouts.base', [
    'title' => 'Complete HitPay Account Verification with Singpass',
    'preheader' => 'Complete HitPay Account Verification with Singpass',
])

@section('content')
    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">
        <div style="text-align: center">
            <img style="color: #222222; font-family: sans-serif; font-weight: 300; line-height: 1.4; margin: 0; Margin-bottom: 30px; font-size: 35px; text-transform: capitalize;" src="{{ asset('hitpay/logo-000036.png') }}" width="128">
        </div>
        <p>
            Hello! <br>
            This email is to remind you to complete your HitPay account verification using MyInfo. This is a mandatory requirement as per the Monetary Authority of Singapore AML/CFT requirements of payments service providers.
        </p>
        <p>
            Below is the step by step guide on how to complete your MyInfo verification <br>
            1) Login to HitPay Dashboard <br>
            2) Navigate to Settings > Account Verification <br>
            3) Select Individual or Company Verification and complete verification using Singpass <br>
            (If you have signed up as an individual, select the individual verification option and if you have signed up as a company / sole proprietor or non-profit, select the company verification) <br>
        </p>
        <p>
            You may also refer to the guide below on how to verify your account using MyInfo. <br>
            <a href="https://hitpay.zendesk.com/hc/en-us/articles/900006274443-How-to-verify-my-account-using-MyInfo-">Click</a>
        </p>
        <p>
            If you have any questions, you can send us an email or reach out to us on WhatsApp at +65 98644718.
        </p>
        <p>Thank You,</p>
        <p>Team HitPay</p>
    </td>
@endsection
