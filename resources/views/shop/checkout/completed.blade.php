@extends('shop.layouts.app', [
    'title' => 'Checkout ',
])

@push('head-stack')
    <style>
        .justify-content-end .nav-link.active {
            border: 2px #000 solid;
        }

        .justify-content-end .nav-link {
            border-radius: .25rem;
            border: 1px #ebecf0 solid;
        }

        .checkout-container .checkout-details {
            padding-right: 125px;
        }

        .checkout-container .checkout-form {
            padding-left: 60px;
        }

        .checkout-container .checkout-form .card-body {
            padding: 0;
        }

        ..checkout-success {
            padding-top: 50px !important;
        }

        @media only screen and (max-width:768px) {    

            .checkout-container .checkout-details {
                padding-right: 15px;
                padding-bottom: 40px;
            }

            .checkout-container .checkout-form {
                padding-left: 15px;
            }

            .checkout-container .checkout-form .card-body {
                padding-right: 15px;
                padding-left: 15px;
            }
        }

        @media only screen and (min-width:992px) {
            .checkout-container:not(.checkout-success):before {
                height: 100%;
                width: 50%;
                position: fixed;
                content: " ";
                top: 0;
                right: 0;
                background: #fff;
                -webkit-animation: background-shadow .6s;
                animation: background-shadow .6s;
                -webkit-animation-fill-mode: both;
                animation-fill-mode: both;
                -webkit-transform-origin: right;
                -ms-transform-origin: right;
                transform-origin: right
            }
        }

        @-webkit-keyframes background-shadow {
            0% {
                -webkit-transform: scaleX(.9);
                transform: scaleX(.9);
                opacity: 0;
                box-shadow: none
            }
            to {
                -moz-box-shadow:  15px 0 30px 0 rgba(0, 0, 0, .80);
                -webkit-box-shadow:  15px 0 30px 0 rgba(0, 0, 0, .80);
                -webkit-transform: scaleX(1);
                transform: scaleX(1);
                opacity: 1;
                /*box-shadow: 15px 0 30px 0 #EFEFF4*/
                box-shadow: 15px 0 30px 0 rgba(0, 0, 0, .80)
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
                        <p class="mb-4"><img class="img-fluid" src="{{ asset('icons/check.png') }}" alt=""></p>
                        <h3 class="mb-3">Completed!</h3>
                        <p>Payment has already been completed.</p>
                    </div>            
                </div>
            </div>    
        </div>
    </body>    
@endsection