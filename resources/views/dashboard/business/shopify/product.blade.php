@extends('layouts.business', [
    'title' => 'Locations - Shopify'
])

@section('business-content')
    <div class="row">
        <div class="col-12 col-md-9 col-lg-8 mb-4">
            <a href="{{ route('dashboard.business.integration.shopify.home', [
                $business->getKey(),
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back
            </a>
        </div>
        <div class="col-12 col-md-9 col-lg-8 main-content">
            <div class="card shadow-sm mb-3">
                <div class="card-body p-4">
                    <label class="small text-uppercase text-muted mb-3">Integrations - Shopify</label>
                    <h2 class="text-primary mb-0 title">Sync Products</h2>
                </div>
                <div class="card-body bg-light border-top p-4">
                    @if ($number_of_shopify_products > 1)
                        @if ($number_of_shopify_products_in_hitpay > 1)
                            We found {{ $number_of_shopify_products }} products in your Shopify store and {{ $number_of_shopify_products_in_hitpay }} of them are already synced into HitPay.
                        @elseif ($number_of_shopify_products_in_hitpay === 1)
                            We found {{ $number_of_shopify_products }} products in your Shopify store and only 1 of them is already synced into HitPay.
                        @else
                            We found {{ $number_of_shopify_products }} products in your Shopify store.
                        @endif
                    @elseif ($number_of_shopify_products === 1)
                        We found only 1 product in your Shopify store.
                    @else
                        No product were found in your Shopify store.
                    @endif
                </div>
                <div class="card-body border-top p-4">
                    @if ($number_of_shopify_products > 0)
                        <form method="post" action="{{ route('dashboard.business.integration.shopify.setting.product.sync', $business->getKey()) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sync mr-2"></i>
                                @if ($number_of_shopify_products_in_hitpay > 0)
                                    Sync Again
                                @else
                                    Sync Now
                                @endif
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
