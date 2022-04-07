@extends('shop.layouts.app', [
    'title' => 'Checkout ',
])

@push('head-stack')
    <style>
        @media only screen and (min-width:992px) {
            .checkout-container:not(.checkout-success):before {
                height: 100%;
                width: 50%;
                position: fixed;
                content: " ";
                top: 0;
                right: 0;
                background: #fff;
            }
        }
    </style>
@endpush

@section('root-content')
    <body>
        <div id="app" class="checkout-container checkout-success">
            <div class="row">
                <div class="checkout-success col-md-12">                                                    
                    <div class="align-self-center text-center">
                        <p class="mb-4"><img class="img-fluid" src="{{ asset('icons/logo.png') }}" alt="HitPay logo" width="300"></p>
                        <h3 class="mb-3">Error!</h3>
                        <p>Please complete your setup in HitPay</p>
                        <a href="{{ $referer }}" class="btn btn-success mt-5">Back to Merchant Page</a>    
                    </div>            
                </div>
            </div>   
        </div>
    </body>
@endsection