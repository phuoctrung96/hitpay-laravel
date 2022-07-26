@extends('layouts.business', [
    'title' => 'Point of sale for ' . $business->name,
])

@section('business-content')
  <business-point-of-sale
                :tax_settings="{{json_encode($tax_settings)}}"
                :categories="{{json_encode($categories)}}"
                :featuredproducts="{{json_encode($featured_products)}}"
                :featured_product_attrs="{{json_encode($featured_products_attrs)}}"></business-point-of-sale>
                <div class="row">
                    <div class="col-12 col-sm-12 col-md-12 col-lg-8">
                        <business-help-guide :page_type="'point_of_sale'"></business-help-guide>
                    </div>
                </div>
@endsection

@push('body-stack')
    <script>
        window.StripePublishableKey = '{{ $stripePublishableKey }}';
    </script>
    <script src="https://js.stripe.com/v3/"></script>
    <script src="https://js.stripe.com/terminal/v1/"></script>
    <script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>
@endpush
