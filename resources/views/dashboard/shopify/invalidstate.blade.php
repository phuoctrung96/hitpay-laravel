@extends('layouts.login', [
    'title' => __('Shopify Invalid State'),
])

@section('login-content')
    <div class="login-register-layout">
        <div class="left-panel d-flex flex-md-column justify-content-center justify-content-md-between align-items-center">
            <svg
                class="align-self-start"
                height="41"
                viewBox="0 0 576 144">
                <use xlink:href='/images/hitpay.svg#hitpay'></use>
            </svg>

            <span class="main-text d-none d-md-block">
                You are just a few clicks away from accepting payments
            </span>

            <div class="flex-column bottom-text d-none d-md-flex">
                <span class="text-center">Within 10 minutes, we were up & running and enjoying much lower transactional fees compared to alternate payment methods.</span>
                <span class="text-right mt-1">-Ice Cream & Cookie Co</span>
            </div>
        </div>

        <div class="main-content d-flex flex-column justify-content-center align-items-center flex-grow-1 p-2 p-md-5">
            <div class="header">
                <h2>Session Expired</h2>
            </div>
            <p class="form-text">Your session has expired. Please try again</p>
            <a href="{{ $url }}" class="btn btn-primary">Try Again</a>
        </div>
    </div>
@endsection
