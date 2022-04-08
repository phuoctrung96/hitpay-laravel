@extends('layouts.root')

@php
    $customisation = $business->getStoreCustomisationStyles();
    $logo = $business->logo;
    if ($logo) $business_logo = $logo->getUrl();
@endphp
@push('head-stack')
    <style>
        html,
        body, #app {
            height: 100%;
        }

        .logo-default {
            width: 30px;
            height: 30px;
        }

        .app-content{
            min-height: 70%;
            overflow-x: hidden;
        }

        .app-banner .promo-banner{
            background-color: #0D8964;
            text-align: center;
            font-size: 16px;
            min-height: 36px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #FFF;
            line-height: 1.5;
            padding: 4px 0px 5px;
        }

        .footer-shop {
            background: #FFF;
        }

        .footer-shop a {
            color: #1E1E1F;
        }

        .footer-shop .email a{
            color: #002771;
        }

        .intro{
            margin:  0px 69px 0px 0px;
        }

        .search {
            margin: 0px 36px 0px 0px;
            background: transparent;
        }

        .search button{
            background: transparent;
            border: 0;
            outline: none;
        }

        .search img {
            width: 27px;
            height: auto;
        }

        .search .header-search-disable {
            display: none;
        }

        .app-search{
            background: #F2F2F2;
            position: relative;
            z-index: 999;
        }

        .app-search .form-search-product{
            padding: 12px 0px;
            display: none;
            position: relative;
        }

        .app-search .ip-search-product{
            width: 100%;
            height: 40px;
            border:  1px solid #D4D6DD;
            padding: 10px 40px 10px 15px;
        }

        .app-search .ip-search-product:focus{
            outline: none;
            box-shadow: none;
        }

        .app-search .button-search-product{
            background-color: #FFF;
            border: 0;
            outline: none;
            position: absolute;
            right: 5px;
            top: 18px;
        }

        .button-search-product {
            background-color: #FFF;
            border: 0;
        }

        .button-search-product img {
            width: 27px;
            height: auto;
        }

        .navbar .cart{
            position: relative;
        }
        
        .cart img {
            width: 32px;
            height: auto;
        }

        .cart .cart-quantity{
            position: absolute;
            top: -6px;
            right: -8px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            text-align: center;
            font-size: 10px;
            height: 18px;
            padding: 1px 0px 0px 0px;
            font-weight: 600;
            background-color: {{$customisation['main_color']}};
            color: {{$customisation['button_text_color']}};
        }

        .modal {
            position: fixed;
            top: 0;
            right: 0;
            left: auto;
            z-index: 9999;
            width: 420px;
            height: auto;
            box-shadow:  1px 1px 10px 2px rgb(232 233 235 / 50%);
        }

        .modal-header{
            padding: 25px 0px 25px;
            margin:  0px 15px 0px;
            border-bottom: 1px solid #dee2e6;
        }

        .modal-header h5{
            margin: 0;
            font-size: 20px;
            padding: 3px 0px 0px;
        }

        .modal-body{
            padding: 25px 15px 22px;
        }

        .modal-content{
            border-radius: 0;
            border: 1px solid #dee2e6;
        }

        .modal-dialog {
            margin: 0px;
        }

        .product-img-small {
            width: 60px;
            height: auto;
        }

        .btn-close{
            cursor: pointer;
            position: relative;
            top: -2px;
            right: 2px;
        }

        .btn-close img{
            width: 14px;
            height: 14px;
        }

        .modal .modal-body .thumbnail {
            width: 60px;
            padding: 5px 0px 0px 0px;
            float: left;
        }

        .modal .modal-body .product-title{
            width: calc(100% - 120px);
            padding:  0px 15px 0px 20px;
            float: left;
            font-size: 16px;
        }

        .modal .modal-body .cart-quantity{
            text-align: right;
            width: 60px;
            float: left;
        }

        .modal .modal-body .dl-view-cart a{
            display: block;
            border: 1px solid #D4D6DD;
            width: 100%;
            padding: 6px 10px;
            text-align: center;
            margin: 15px 0px 10px;
        }

        .modal .modal-body .dl-continue-shipping{
            text-align: center;
        }

        .modal .modal-body .dl-continue-shipping span{
            text-decoration: underline;
            font-size: 15px;
        }

        .modal .modal-body .btn-continue-shipping{
            cursor: pointer;
        }

        @media (max-width: 767px) {
            .app-banner .promo-banner{
                font-size: 13px;
                min-height: 32px;
                padding: 2px 0px 3px;
            }

            .intro{
                margin:  0px 9px 0px 0px;
            }

            .search {
                margin: 0px 12px 0px 0px;
                background: transparent;
            }

            .search button{
                position: relative;
                top: -1px;
            }

            .search img {
                width: 23px;
            }

            .cart img {
                width: 25px;
            }

            .modal{
                width: 100%;
            }

            .modal-dialog{
                max-width: 100%;
            }

            .modal-header h5{
                font-size: 18px;
                padding:  2px 0px 0px;
            }
        }

    </style>
@endpush
@section('root-content')
    <body>
    <div id="app" class="bg-white">
        <div class="modal" id="dialog-add-cart">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="p-title">Just added to your cart</h5>
                        <span class="btn-close" id="dialog-add-cart-close">
                            <img src="/images/delete_icon.svg" alt="delete" class="btn-close-add-to-cart">
                        </span>
                    </div>

                    <div class="modal-body">
                        <div id="dl-add-to-cart-body" class="clearfix">
                        
                        </div>

                        <div class="dl-view-cart">
                            <a href="{{ route('shop.cart', $business->getKey()) }}">View cart (1)</a>
                        </div>
                        <div class="dl-continue-shipping">
                            <span class="btn-continue-shipping" data-dismiss="modal">Continue shipping</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="errorBar" class="alert alert-danger rounded-0 border-top-0 border-left-0 border-right-0 d-none mb-0"
             role="alert">
            <div class="container text-center small"></div>
        </div>
        <div id="demo" class="alert alert-warning rounded-0 border-top-0 border-left-0 border-right-0 mb-0 d-none">
            <div class="container text-center small">
                <strong>Demo in test mode.</strong> This app is running in test mode. You will not be charged.
            </div>
        </div>
        <nav class="navbar navbar-expand-md navbar-light navbar-main clearfix" id="navbar">
            <div class="container d-block">
                <div class="d-flex justify-content-between">
                    <div class="logo">
                        <a href="{{ route('shop.business', $business->identifier ?? $business->getKey()) }}" class="d-flex align-items-center">
                            <div class="header-logo" class="d-md-block d-lg-block">
                                @if(isset($business_logo))
                                    <img src="{{ $business_logo }}">
                                @else
                                    <img src="{{asset('images/home_icon.svg')}}" class="logo-default" alt="logo" />
                                @endif
                            </div>
                            <div class="header-name">
                                <h2 class="mb-0">{{$business->name}}</h2>
                            </div>
                        </a>
                    </div>
                    <div class="d-flex align-items-center">
                        @if($business->introduction)
                        <div class="intro my-auto d-none d-md-block d-lg-block">
                            <a class="nav-link"
                               href="{{ route('shop.introduction', $business->identifier ?? $business->getKey()) }}">
                                About Us
                            </a>
                        </div>
                        @endif
                        <!-- <div class="search">
                            <div class="header-search-disable my-auto" id="search-disable">
                                <button>
                                    <img src="{{asset('images/search_icon_disable.svg')}}">
                                </button>
                            </div>
                            <div class="header-search my-auto" id="search-enable">
                                <button id="search-dark">
                                    <img src="{{asset('images/search_icon.svg')}}">
                                </button>
                            </div>
                        </div> -->
                        <div class="cart my-auto">
                            @if (session()->has('cart-'.$business->getKey()))
                                <?php
                                    $quantity = 0;
                                    foreach (session()->get('cart-' . $business->getKey())['products'] as $product) {
                                        $quantity += $product['quantity'];
                                    }
                                ?>
                                <a href="{{ route('shop.cart', $business->getKey()) }}">
                                    <img src="{{asset('images/cart_icon.svg')}}" id="cart-dark">
                                    <small id="basket-quantity" class="{{$quantity > 0 ? 'cart-quantity' : ''}}" data-value="{{$quantity}}">{{ $quantity?$quantity:'' }}</small>
                                </a>
                            @else
                                <a href="{{ route('shop.cart', $business->getKey()) }}">
                                    <img src="{{asset('images/cart_icon.svg')}}" id="cart-dark">
                                    <small id="basket-quantity" data-value="0"></small>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </nav>
        <!-- <div class="app-search">
            <div class="container">
                <form action="" method="POST" class="form-search-product" id="form-search">
                <input id="inputSearchProduct" placeholder="Search" class="ip-search-product">
                <button type="button" class="button-search-product">
                    <img src="{{asset('images/search_icon.svg')}}">
                </button>
            </form>
            </div>
        </div> -->

        <div class="app-content">
            @yield('app-content')
        </div>
        
        <div class="footer-shop border-top">
            <div class="container container-md small">
                <div class="row">
                    <div class="col-md-2">
                        <div class="footer-logo d-none d-md-block d-lg-block">
                            <a href="{{ route('shop.business', $business->identifier ?? $business->getKey()) }}">
                                @isset ($business_logo)
                                    <img src="{{ $business_logo }}">
                                @endisset
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h3>{{$business->name}}</h3>
                        <div class="footer-logo d-md-none d-lg-none">
                            <a href="{{ route('shop.business', $business->identifier ?? $business->getKey()) }}">
                                @isset ($business_logo)
                                    <img src="{{ $business_logo }}">
                                @endisset
                            </a>
                        </div>
                        <p class="email"><a href="mailto:{{$business->email}}">{{$business->email}}</a></p>
                        <p class="copyright d-none d-md-block d-lg-block">Copyright &copy; {{ date('Y') }}
                            <a href="https://hit-pay.com" target="_blank">{{ config('app.name') }}</a>
                            . All rights reserved.
                        </p>
                    </div>
                    <div class="col-md-4">
                        <ul class="list-inline">
                            <li class="list-inline-item">
                                <a href="{{ url('https://www.hitpayapp.com/termsofservice') }}" target="_blank">Terms of
                                    Service</a>
                            </li>
                        </ul>
                        <div class="accepted-payment">
                            <p>
                                <span><img src="{{asset('/images/paynow_icon.svg')}}" width="55px"></span>
                                <span><img src="{{asset('/images/applepay_icon.svg')}}" width="55px"></span>
                                <span><img src="{{asset('/images/googlepay_icon.svg')}}" width="55px"></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container">
                <p class="copyright d-lg-none d-md-none d-xs-block d-sm-block">Copyright &copy; {{ date('Y') }}
                    <a href="https://hit-pay.com" target="_blank">{{ config('app.name') }}</a>
                    . All rights reserved.
                </p>
            </div>
        </div>
    </div>
    <script>
        window.HitPay = @json($hitpay_script_variables);

        window.addEventListener("DOMContentLoaded", ready);
        function ready() {
            document.addEventListener("click", hiddenDialogAddToCart);
        }

        function hiddenDialogAddToCart(event) {
            if ( event.target.matches(".btn-close-add-to-cart") || !event.target.closest("#dialog-add-cart")) {
                document.getElementById('dialog-add-cart').style.display = "none";
                return;
            } else if ( event.target.matches(".btn-continue-shipping") || !event.target.closest("#dialog-add-cart")) {
                document.getElementById('dialog-add-cart').style.display = "none";
            }
            
        }
    </script>
    @stack('body-stack')
    </body>
@endsection
