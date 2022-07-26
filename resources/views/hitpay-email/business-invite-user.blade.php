@extends('hitpay-email.layouts.base', [
    'title' => $title,
    'preheader' => $title,
])

@section('content')
    <p><img src="{{ asset('hitpay/logo-000036.png') }}" width="160" alt="HitPay"></p>
    <p>Hi There!</p>
    <p>You have been invited to join {{$business->name}}</p>
    <p>
        <a href="{{$url}}" style="background-color: #011B5F; font-size: 14px; font-family: Helvetica, Arial, sans-serif; font-weight: bold; text-decoration: none; padding: 10px 16px; color: #ffffff; border-radius: 5px; display: inline-block; mso-padding-alt: 0;">
            View Invitation
        </a>
    </p>
    <p>Best,</p>
    <p>Team HitPay</p>
@endsection
