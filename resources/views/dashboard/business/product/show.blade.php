@php($product_link = $product->shortcut_id
    ? URL::route('shortcut', $product->shortcut_id)
    : URL::route('shop.product', [
        $product->business_id,
        $product->getKey(),
    ]))

@extends('layouts.business', [
    'title' => 'Products'
])

@section('business-content')
    <input id="product-url" class="d-none" value="{{ $product_link }}" title="Store Link" disabled>
    <div class="row">
        <div class="col-12 col-md-9 col-lg-8 mb-4">
            <a href="{{ route('dashboard.business.product.index', [
                $business->getKey(),
                'page' => request('index_page', 1),
                'shopify_only' => request('index_shopify_only', 0),
                'status' => request('index_status', 'published'),
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to Products
            </a>
        </div>
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <label class="small text-uppercase text-muted mb-3">Products</label>
                    <h2 class="text-primary mb-0 title">{{ $product->name }}</h2>
                    <p class="text-dark small mt-3"><span class="text-muted"># {{ $product->getKey() }}</span></p>

                    <div class="mb-0">
                        <p class="small text-muted mb-2">{{ $product_link }}</p>
                        <a id="copyButton" class="small" href="#">Click here to copy product link</a>
                    </div>
                </div>
                @if ($product->shopify_id)
                    <div class="alert alert-warning border-left-0 border-right-0 rounded-0">
                        <i class="fab fa-shopify mr-2"></i> Synced from Shopify
                    </div>
                @endif
                @if ($image = $product->display('image', null, true))
                    <div class="card-body bg-light border-top p-4">
                        <img src="{{ $image }}" class="img-fluid rounded border" style="max-width: 200px">
                    </div>
                @endif
                <div class="card-body border-top p-4">
                    @if ($product->description)
                        <p>{{ $product->description }}</p>
                    @endif
                    <p class="mb-0">Price: <span class="font-weight-bold">{{ $product->display('price') }}</span></p>
                </div>
                @foreach ($product->variations as $variation)
                    <div class="card-body bg-light border-top p-4">
                        <div class="media">
                            <div class="media-body">
                                @if ($variation->description)
                                    <p class="font-weight-bold mb-2">{{ $variation->description }}</p>
                                @endif
                                <p class="text-dark small mb-2">Price: <span class="text-muted">{{ getFormattedAmount($product->currency, $variation->price) }}</span></p>
                                <p class="text-dark small mb-0">Quantity Available: <span class="text-muted">{{ $variation->quantity }}</span></p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection

@push('body-stack')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('copyButton').addEventListener('click', function () {
                event.preventDefault();

                target = document.getElementById('product-url');

                target.classList.remove('d-none');
                target.removeAttribute('disabled');

                var currentFocus = document.activeElement;

                target.focus();
                target.setSelectionRange(0, target.value.length);

                var succeed;

                try {
                    succeed = document.execCommand('copy');

                    alert('Store Link Copied');
                } catch (e) {
                    succeed = false;
                }

                if (currentFocus && typeof currentFocus.focus === 'function') {
                    currentFocus.focus();
                }

                target.setAttribute('disabled', true);
                target.classList.add('d-none');

                return succeed;
            });
        });
    </script>
@endpush
