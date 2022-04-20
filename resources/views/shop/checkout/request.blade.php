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
  $show_test_payment = App::environment('sandbox');
@endphp

@push('head-script')
  <meta name="color-scheme" content="dark light">

  @if ($umami)
    <script defer data-website-id="{{ config('checkout.umamiAppId') }}" src="{{ $umami }}"></script>
  @endif
@endpush

@section('root-content')
    <body>
        <div id="app">
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
              :default_url_completed="'{{ $defaultUrlCompleted }}'"
              :allow_deep_link_panel="{{ json_encode($allowDeepLinkPanel) }}"
              :mode="'{{ $mode }}'"
              :symbol="'{{ $symbol }}'"
              @if(isset($cashback)):cashback="{{ json_encode($cashback)}}" @endif
              @if(isset($campaignRule)) :campaign_rule="{{json_encode($campaignRule)}}"@endif
              :default_method="{{ json_encode($defaultMethod) }}"
              :show_test_payment="{{ json_encode($show_test_payment) }}"
              :zero_decimal="{{ json_encode($zeroDecimal) }}"
              @if(isset($expire_date)) :expire_date="{{ json_encode($expire_date) }}"@endif
              >
          </shop-checkout-request-form>
        </div>

        <script>
            window.HitPay = @json($hitpay_script_variables);
            window.StripePublishableKey = '{{ $stripePublishableKey }}';
        </script>
        <script src="//js.stripe.com/v3/"></script>
        <script src="//js.stripe.com/terminal/v1/"></script>
        <!--<script src="//cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>-->
        <script src="https://js.pusher.com/7.0/pusher.min.js"></script>
    </body>
@endsection
