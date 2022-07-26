@php($title = 'Bank Accounts')

@extends('layouts.login')

@push('head-stack')
    <script>
        window.Business = @json($business->toBladeModel());
        window.User = @json(Auth::user()->load('businessUsers')->toBladeModel());
    </script>
@endpush

@section('login-content')
        <business-onboard-paynow-create
            :bank_fields="{{ json_encode($bank_fields) }}"
            :provider="{{ json_encode($provider) }}"
            :banks_list="{{ json_encode($banks_list) }}"
            :success_message="{{ json_encode($success_message) }}">
        </business-onboard-paynow-create>
@endsection
