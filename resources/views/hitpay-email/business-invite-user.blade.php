@extends('hitpay-email.layouts.base', [
    'title' => $title,
    'preheader' => $title,
])

@section('content')
    <p><img src="{{ asset('hitpay/logo-000036.png') }}" width="160" alt="HitPay"></p>
    <p>Hi There!</p>
    <p>You have been invited to join {{$business->name}}</p>
    <p>
        <a href="{{$url}}" class="btn btn-primary">View Invitation</a>
    </p>
    <p>Best,</p>
    <p>Team HitPay</p>
@endsection
