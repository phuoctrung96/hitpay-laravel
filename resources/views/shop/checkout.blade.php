@extends('shop.layouts.app', [
    'title' => 'Checkout ',
])

@php
  $customisation = $business->checkoutCustomisation();
  $logo = $business->logoUrl();
  $umami = config('checkout.umamiUrl');
  $cashbackFor = config('checkout.cashbackFor');
  $cashbackAmount = config('checkout.cashbackAmount');
  $allowDeepLinkPanel = config('checkout.allowDeepLinkPanel');
@endphp

@push('head-script')
  @if ($umami)
    <script defer data-website-id="{{ config('checkout.umamiAppId') }}" src="{{ $umami }}"></script>
  @endif
@endpush

@push('head-stack')
    <style>
        .product-details {
            padding-top: 160px;
        }

        .checkout-footer {
            padding-top: 150px;
            font-size: 12px;
        }

        .checkout-disclaimer {
            padding-top: 60px;
            font-size: 14px;
        }

        .checkout-footer-mobile {
            display: none;
        }

        .checkout-footer ul {
            list-style-type: none;
            padding-left: 0px;
            padding-top: 8px;
        }

        .checkout-footer-mobile ul {
            list-style-type: none;
            padding-left: 0px;
        }

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

        #paynow-online-qr-code img {
            margin: auto;
        }

        @media only screen and (max-width:320px) {
            img.brand-visa {
                height: 8px;
            }

            img.brand {
                height: 10px;
            }

            img.method-wechat {
                height: 20px;
            }

            img.method-alipay {
                height: 40px;
            }

            img.method-paynow {
                height: 25px;
            }
        }

        @media only screen and (max-width:768px) {
            .product-details {
                padding-top: 40px;
                text-align: center;
            }

            .checkout-footer {
                display: none;
            }

            .checkout-footer-mobile {
                display: block;
                padding-top: 40px;
                padding-bottom: 20px;
                font-size: 12px;
            }

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
    </style>
@endpush

@section('root-content')
    <body>
        <div id="app" class="checkout-container @if ($charge->status === 'succeeded') checkout-success @endif">
            @if ($charge->status === 'succeeded')
                <div class="row">
                    <div class="checkout-success col-md-12">
                        <div class="align-self-center text-center">
                            <h3 class="mb-3">Completed!</h3>
                            <p>Payment has already been completed.</p>
                            <a href="{{ $referer }}" class="btn btn-success mt-5">Back to Merchant Page</a>
                        </div>
                    </div>
                </div>
            @else
              <shop-checkout-request-form
                  :charge="{{ $charge }}"
                  :business="{{ json_encode($business->getFilteredData()) }}"
                  :customisation="{{ $customisation }}"
                  :countries="{{ json_encode($countries) }}"
                  :methods="{{ json_encode($paymentMethods) }}"
                  :amount="'{{ $amount }}'"
                  :cashback_for="'{{ $cashbackFor }}'"
                  :cashback_amount="'{{ $cashbackAmount }}'"
                  :data="{{ json_encode($data) }}"
                  :referer="'{{ $referer }}'"
                  :merchant_image="'{{ $logo }}'"
                  @if(isset($cashback)):cashback="{{ json_encode($cashback)}}" @endif
                  @if(isset($campaignRule)):campaign_rule="{{json_encode($campaignRule)}}"@endif
                  :default_url_completed="'{{ $defaultUrlCompleted }}'"
                  :without_payment_request="{{ true }}"
                  :allow_deep_link_panel="{{ json_encode($allowDeepLinkPanel) }}"
                  :symbol="'{{ $symbol }}'"
                  :zero_decimal="{{ json_encode($zeroDecimal) }}">
              </shop-checkout-request-form>
            @endif
        </div>
        @if ($charge->status !== 'succeeded')
            <script>
                window.HitPay = @json($hitpay_script_variables);
                window.StripePublishableKey = '{{ $stripePublishableKey }}';
            </script>
            <script src="//js.stripe.com/v3/"></script>
            <script src="//js.stripe.com/terminal/v1/"></script>
            <!--<script src="//cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>-->
            <script src="https://js.pusher.com/7.0/pusher.min.js"></script>
        @endif
    </body>
@endsection
