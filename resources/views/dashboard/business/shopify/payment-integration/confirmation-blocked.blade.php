@extends('layouts.business', [
    'title' => 'Shopify'
])

@section('business-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card shadow-sm mb-3">
                <div class="card-body p-4">
                    <label class="small text-uppercase text-muted mb-3">Integrations</label>
                    <h2 class="text-primary mb-3 title">Blocked to Shopify Payment</h2>
                    <p>You are blocked connected to
                        <span class="font-weight-bold">{{ $shopifyDomain }}</span> because you already have {{ \App\BusinessShopifyStore::MAX_STORES }} connected.
                    </p>
                </div>
                <div class="card-body bg-light border-top p-4">
                    <label class="small text-muted text-uppercase">Shop Name</label>
                    <p>{{ $shopifyName }}</p>
                    <label class="small text-muted text-uppercase">MyShopify Domain</label>
                    <p>{{ $shopifyDomain }}</p>
                </div>
                <div class="card-body border-top p-4">
                    <a class="btn btn-primary" href="{{ $shopifyAdminLink }}">
                        <i class="fab fa-shopify mr-2"></i> Go to Shopify
                    </a>

                    <a class="btn btn-primary" href="{{ $shopifyAdminLink }}">
                        <i class="fab fa-store mr-2"></i> Go to List Store Connected
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('body-stack')

@endpush
