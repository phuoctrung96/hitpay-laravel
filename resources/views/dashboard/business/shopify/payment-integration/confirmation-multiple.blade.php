@extends('layouts.business', [
    'title' => 'Shopify'
])

@section('business-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card shadow-sm mb-3">
                <div class="card-body p-4">
                    <label class="small text-uppercase text-muted mb-3">Integrations</label>
                    <h2 class="text-primary mb-3 title">Connected to Shopify Payment</h2>
                    <p>
                        This hitpay account already has more than one shopify store connected:
                    </p>
                    <ul>
                        @foreach($oldShopDomains as $oldShopDomain)
                            <li><span class="font-weight-bold">{{ $oldShopDomain }}<span></li>
                        @endforeach
                    </ul>
                    <p>
                        Would you like to connect another store as well?
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
