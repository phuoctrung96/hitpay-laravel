@extends('layouts.root', [
    'title' => 'PayNow Demo - '.$business->getName(),
])

@section('root-content')
    <body class="bg-light-primary">
    <div id="app">

        <div class="container pt-4 pt-phone-5 pb-5">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-7 col-xl-6 order-1 order-md-2">
                    <div class="card shadow-sm mb-4 mb-phone-5">
                        <div class="card-body text-center py-4">
                            <img src="{{ asset('hitpay/logo-000036.png') }}" height="48" alt="{{ $app_name }}">
                        </div>
                        <div class="alert alert-warning rounded-0 border-left-0 border-right-0 mb-0">
                            <div class="container text-center small">
                                <strong>Demo in test mode.</strong> This app is running in test mode. You will not be charged.
                            </div>
                        </div>
                        <div class="card-body text-center py-4 border-bottom">
                            @if ($intent instanceof \App\Business\PaymentIntent)
                                <p class="small text-muted font-weight-light text-uppercase">Paying to</p>
                                <h2 class="h5 font-weight-bold mb-0">{{ $business->getName() }}</h2>
                            @elseif ($intent instanceof \App\Business\RefundIntent)
                                <h2 class="h5 font-weight-bold">{{ $business->getName() }}</h2>
                                <p class="small text-muted font-weight-light text-uppercase mb-0">Refunding to</p>
                                <h2 class="h5 font-weight-bold mb-0">{{ str_replace('proxy:', '', $intent->payment_provider_account_id) }}</h2>
                            @endif
                        </div>
                        <div class="card-body text-center py-4">
                            <img src="{{ asset('icons/payment-methods/paynow.png') }}" alt="PayNow" class="img-fluid bg-light rounded p-4 mb-4">
                            <h1 class="h2 font-weight-bold mb-3">{{ getFormattedAmount($intent->currency, $intent->amount) }}</h1>
                        </div>
                        @if ($intent->status === 'succeeded')
                            <div class="card-body text-center py-4 border-bottom bg-success">
                                <span class="h4 font-weight-bold mb-0 text-white">Paid</span>
                            </div>
                        @else
                            <form action="{{ $url }}" method="post" class="card-body text-center py-0">
                                @csrf
                                <button type="submit" class="btn btn-success btn-lg">Pay Now</button>
                            </form>
                        @endif
                        <div class="card-body text-center pt-0 pb-4"></div>
                    </div>
                    <p class="small text-center">
                        <a href="https://www.hitpayapp.com"><i class="fas fa-home"></i> @lang('Home')</a>
                    </p>
                    <ul class="list-inline small text-center mb-0">
                        <li class="list-inline-item">
                            <a href="https://www.hitpayapp.com/termsofservice">@lang('Terms of Service')</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    </body>
@endsection
