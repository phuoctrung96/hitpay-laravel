@php($title = 'Bank Accounts')

@extends('layouts.login')

@section('login-content')
        <business-onboard-paynow-create
            :business="{{ json_encode($business) }}"
            :provider="{{ json_encode($provider) }}"
            :banks_list="{{ json_encode($banks_list) }}"
            :success_message="{{ json_encode($success_message) }}">
        </business-onboard-paynow-create>
@endsection
