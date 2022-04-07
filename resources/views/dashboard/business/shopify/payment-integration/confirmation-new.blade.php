@extends('layouts.business', [
    'title' => 'Shopify'
])

@section('business-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card shadow-sm mb-3">
                <div class="card-body p-4">
                    <label class="small text-uppercase text-muted mb-3">Integrations</label>
                    <h2 class="text-primary mb-3 title">{{ $business->name }} will connected to Shopify Payment</h2>
                    <p>Your HitPay account will be connecting to
                        <span class="font-weight-bold">{{ $shopifyDomain }}</span> and and it will be added as a payment app. If you want to stop using this app or want to connect to another shopify account, you may uninstall the app from your Shopify dashboard.
                    </p>
                </div>
                <div class="card-body bg-light border-top p-4">
                    <label class="small text-muted text-uppercase">Shop Name</label>
                    <p>{{ $shopifyName }}</p>
                    <label class="small text-muted text-uppercase">MyShopify Domain</label>
                    <p>{{ $shopifyDomain }}</p>
                </div>
                <div class="card-body border-top p-4">
                    <a class="btn btn-primary" href="{{ $continueLink }}">
                        <i class="fab fa-shopify mr-2"></i> Continue
                    </a>
                    <a class="btn btn-primary" href="{{ $cancelLink }}">
                        <i class="fab fa-remove mr-2"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('body-stack')

@endpush
