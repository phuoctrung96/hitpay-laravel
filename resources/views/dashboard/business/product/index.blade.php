@extends('layouts.business', [
    'title' => 'Products'
])
@php($setupPayment = isset($business) && ($business->paymentProviders()->whereIn('payment_provider', [
    $business->payment_provider,
    \App\Enumerations\PaymentProvider::DBS_SINGAPORE,
])->count() === 0))
@section('business-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 main-content">
            @if (isset($setupPayment) && $setupPayment)
                <div class="alert alert-danger ">
                    <div class="container-fluid text-center">
                        <p class="small mb-0">You can't create <products></products> without enabling payment method.
                            <a href="{{ route('dashboard.business.payment-provider.home', $business->getKey()) }}">Enable now</a>
                        </p>
                    </div>
                </div>
            @else
                <div class="btn-group btn-group-sm mb-3 w-100 shadow-sm">
                    <a class="btn col {{ $status === 'published' ? 'active btn-outline-primary' : 'bg-light' }}" href="{{ route('dashboard.business.product.index', [
                    $business->getKey(),
                    'status' => 'published',
                    'shopify_only' => $shopify_only,
                ]) }}">Published</a>
                    <a class="btn col {{ $status === 'draft' ? 'active btn-outline-primary' : 'btn-light' }}" href="{{ route('dashboard.business.product.index', [
                    $business->getKey(),
                    'status' => 'draft',
                    'shopify_only' => $shopify_only,
                ]) }}">Draft</a>
                </div>
                <div class="form-group">
                    <form class="input-group input-group-lg" action="{{ route('dashboard.business.product.index', [
                    $business->getKey(),
                ]) }}">
                        <input class="form-control border-0 shadow-sm" placeholder="Search Product"
                               title="Search Product" name="keywords" value="{{ request('keywords') }}">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <input type="hidden" name="shopify_only" value="{{ $shopify_only }}">
                        <div class="input-group-append">
                            <button class="btn btn-primary shadow-sm"><i class="fas fa-search"></i></button>
                        </div>
                    </form>
                </div>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="text-primary mb-3 title">Products</h2>
                        <br>
                        <product-url-settings></product-url-settings>
                        @if ($status === 'published')
                            <p>Below products are already published and you customer can view them in the shop or
                                through the product link.</p>
                        @else
                            <p>Manage your inventory.</p>
                        @endif
                        @if ($business->shopify_id && $status === 'published')
                            <div class="custom-control custom-switch mb-3">
                                <input type="checkbox" class="custom-control-input" id="order-related-check"
                                       onchange="check(this)"{{ $shopify_only ? ' checked' : '' }}>
                                <label class="custom-control-label" for="order-related-check">Show Shopify products
                                    only</label>
                            </div>
                        @endif
                        <a class="btn btn-primary"
                           href="{{ route('dashboard.business.product.create', $business->getKey()) }}">
                            <i class="fas fa-plus mr-2"></i> Add Product
                        </a>
                        <a class="btn btn-secondary"
                           href="{{ route('dashboard.business.product.bulk', $business->getKey()) }}">
                            <i class="fas fa-plus mr-2"></i> Add Products In Bulk
                        </a>
                    </div>
                    @if(session('success_message'))
                        <div
                            class="alert alert-success border-left-0 border-right-0 rounded-0 alert-dismissible fade show"
                            role="alert">
                            {{ session('success_message') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
                    @if ($products->count())
                        <business-product-list
                            :status="'{{ request()->get('status') ? request()->get('status') : 'published' }}'"></business-product-list>
                    @else
                        <div class="card-body bg-light border-top p-4">
                            <div class="text-center text-muted py-4">
                                <p><i class="fa fas fa-boxes fa-4x"></i></p>
                                <p class="small mb-0">- No product found -</p>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
    <div class="row">
        <div class="col-12 col-sm-12 col-md-12 col-lg-8 mb-4">
            <business-help-guide :page_type="'online_shop_products'"></business-help-guide>
        </div>
    </div>
@endsection

@push('body-stack')
    <script>
        window.Business = @json($business);
        window.Data = @json($data);
        window.Products = @json($products);
        window.ProductAttrs = @json($product_attrs);

        function check(trigger) {
            let current = new URL(window.location.href);
            let query = current.search;
            let params = new URLSearchParams(query);

            if ($(trigger).is(':checked')) {
                params.set('shopify_only', 1);
            } else {
                params.set('shopify_only', 0);
            }

            current.search = params.toString();
            window.location = current.toString();
        }
    </script>
@endpush
