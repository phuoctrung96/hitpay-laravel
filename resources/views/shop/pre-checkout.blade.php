@php($shippable_countries = count($checkoutOptions['shippings']))
@php($globalShippable = isset($checkout_options['shippings']['global']) && count($checkout_options['shippings']['global']))
@php($avatar_url = asset('hitpay/images/product.jpg'))
@php($customisation = $business->checkoutCustomisation())
@php($customStyles = $business->getStoreCustomisationStyles())
@php($umami = config('checkout.umamiUrl'))

@extends('shop.layouts.app', [
    'title' => 'Order Confirmation - '.$business->getName(),
])

@push('head-script')
  @if ($umami)
    <script defer data-website-id="{{ config('checkout.umamiStoreFrontId') }}" src="{{ $umami }}"></script>
  @endif
@endpush

@push('head-stack')
    <style>
        .label-checkbox .checkmark:after {
            background: {{$customStyles['main_color']}};
        }
    </style>
@endpush

@section('app-content')
<div class="main-app-content precheckout">
    <div class="container">
        <div class="g-top-meta">
            <div class="row">
                <div class="col-7">
                    <a href="{{ url()->previous() }}" class="btn-back mt-3 text-muted"><img src="{{asset('/images/back_icon.svg')}}"/> Back</a>
                </div>
            </div>
        </div>
        <div class="ct-pre-checkout">
            <pre-checkout :customisation="{{$customisation}}"></pre-checkout>
        </div>
    </div>
</div>
@endsection

@push('body-stack')
    <script src="https://js.stripe.com/v3/"></script>
    <script type="text/javascript" defer>
        window.Business = @json((new \App\Http\Resources\Business($business))->toArray(request()->instance()));
        window.CheckoutOptions = @json($checkoutOptions);
        window.TotalCartAmount = {{ $totalCartAmount }};
        window.TotalCartQuantity = {{ $totalCartQuantity }};
        window.Discount = @json($discount);
        window.ShippingDiscount = @json($shipping_discount);
        window.Variations = @json($variations);
    </script>
@endpush
