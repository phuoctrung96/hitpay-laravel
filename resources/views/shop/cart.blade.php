@php($avatar_url = asset('hitpay/images/product.jpg'))
@php($customisation = $business->checkoutCustomisation())

@extends('shop.layouts.app', [
    'title' => 'Cart - '.$business->getName(),
])


@section('app-content')
<div class="main-app-content">
    <div class="container">
        <div class="g-top-meta">
            <a href="{{ url()->previous() }}" class="btn-back"><img src="{{asset('images/back_icon.svg')}}"/> Back</a>
        </div>
        <div class="ct-cart">
            <div class="row">
                <div class="col-12">
                    <h1 class="h1 p-title">Cart</h1>
                    @if (count($variations))
                        <cart-items :customisation = "{{$customisation}}"></cart-items>
                    @else
                        <div class="bg-white rounded shadow-sm pt-3 px-3 mb-3">
                            <div class="text-center text-muted py-4">
                                <p><i class="fa fas fa-shopping-cart fa-4x"></i></p>
                                <p class="small mb-0">- No product found -</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('body-stack')
    <script src="https://js.stripe.com/v3/"></script>
    <script type="text/javascript" defer>
        window.Business = @json((new \App\Http\Resources\Business($business))->toArray(request()->instance()));
        window.Discount = @json($discount);
        window.Variations = @json($variations);
    </script>
@endpush
