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
                    <p>
                        This store <span class="font-weight-bold">{{ $shopifyDomain }}</span> has already been connected to another hitpay account <span class="font-weight-bold">{{ $oldBusiness->name }}</span>. Disconnect the existing connection and try again.
                    </p>
                </div>
                <div class="card-body bg-light border-top p-4">
                    <label class="small text-muted text-uppercase">Shop Name</label>
                    <p>{{ $shopifyName }}</p>
                    <label class="small text-muted text-uppercase">MyShopify Domain</label>
                    <p>{{ $shopifyDomain }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('body-stack')

@endpush
