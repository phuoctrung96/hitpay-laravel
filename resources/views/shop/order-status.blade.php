@php($avatar_url = asset('hitpay/images/product.jpg'))
@php($customisation = $business->checkoutCustomisation())
@php($umami = config('checkout.umamiUrl'))

@extends('shop.layouts.app', [
    'title' => 'Order - '.$business->getName(),
])
@push('head-script')
  @if ($umami)
    <script defer data-website-id="{{ config('checkout.umamiStoreFrontId') }}" src="{{ $umami }}"></script>
  @endif
@endpush
@push('head-stack')
    <style>
        .status-box {
            padding-top: 15%;
        }

        .table > thead > tr > td {
            border-top: none;
        }

        td {
            padding: 1.2rem !important;
        }
    </style>
@endpush
@section('app-content')
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="mb-3">
                    <div class="d-flex justify-content-center align-items-center status-box">
                        @if($status == 'completed')
                            <img src="{{ asset('hitpay/images/success.png') }}" height="100">
                        @else
                            <img src="{{ asset('hitpay/images/failure.png') }}" height="100">
                        @endif
                    </div>
                    <div class="mt-5 mb-3 d-flex justify-content-center">
                        <span class="h5">Your order has been {{$status}}</span>
                    </div>
                    @if($business->thank_message)
                        <div class="mt-2 mb-3 d-flex justify-content-center">
                            <span class="text-muted">{{$business->thank_message}}</span>
                        </div>
                    @endif
                    <div class="d-flex justify-content-center">
                        <span class="text-muted mt-2 mb-3">Order ID: {{$order->id}}</span>
                    </div>
                    @if($business->is_redirect_order_completion)
                        <div class="mt-2 mb-3 d-flex justify-content-center">
                            You will be redirected to <div id="url_redirect"></div> <div id="countdown"></div> sec...
                        </div>
                    @endif
                    <table class="table table-responsive-sm my-5">
                        <thead>
                        <tr>
                            <td scope="col">Item</td>
                            <td scope="col">Quantity</td>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($order->products as $product)
                            <tr>
                                <td>
                                    <a id="productName"
                                       class="d-block">{{ $product->name }}</a>
                                    <small class="text-muted">{{ $product->description }}</small>
                                </td>
                                <td>{{$product->quantity}}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('body-stack')
    <script type="text/javascript" defer>
        var is_redirect_order_completion = {{ $business->is_redirect_order_completion }};
        var url_redirect = @json($business->url_redirect_order_completion);
        
        if(is_redirect_order_completion) {
            var timeleft = 10;
            var downloadTimer = setInterval(function(){
            if(timeleft <= 0){
                clearInterval(downloadTimer);
                window.location.href = url_redirect;
            } else {
                document.getElementById("countdown").innerHTML = "&nbsp;in&nbsp;"+timeleft + "&nbsp;";
            }
            timeleft -= 1;
            }, 1000);
        }

        var url_arr = url_redirect.split('/');
        var origin_url = url_arr[2].split('.');
        document.getElementById("url_redirect").innerHTML = "&nbsp;"+url_arr[2];
        umami('payment_completed');
    </script>
@endpush
