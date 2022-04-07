@extends('layouts.business', [
    'title' => 'Shopify'
])

@section('business-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card shadow-sm mb-3">
                @if ($business->shopify_id)
                    <div class="card-body p-4">
                        <label class="small text-uppercase text-muted mb-3">Integrations</label>
                        <h2 class="text-primary mb-3 title">Connected to Shopify</h2>
                        <p>You have already connected to
                            <span class="font-weight-bold">{{ $business->shopify_domain }}</span> and subscribed to our app. If you want to stop using this app or want to connect to another shopify account, you may uninstall the app from your Shopify dashboard or click the red text below.
                        </p>
                        <a class="small font-weight-bold text-danger" href="#" data-toggle="modal" data-target="#removeShopify">Remove Shopify</a>
                        <div id="removeShopify" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="text-danger modal-title mb-0">Remove Shopify</h5>
                                    </div>
                                    <div class="modal-body">
                                        <p class="text-danger font-weight-bold mb-0">By removing Shopify from HitPay, all the products synced before will be deleted. However, the product purchased in the order will not be affected.</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-dismiss="modal">Close</button>
                                        <form method="post" action="{{ route('dashboard.business.integration.shopify.unauthorize', $business->getKey()) }}">
                                            @csrf
                                            @method('delete')
                                            <button id="removeShopifyButton" type="submit" class="btn btn-danger" onclick="showSpinner()">Confirm Remove</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body bg-light border-top p-4">
                        <label class="small text-muted text-uppercase">Shop Name</label>
                        <p>{{ $business->shopify_name }}</p>
                        <label class="small text-muted text-uppercase">MyShopify Domain</label>
                        <p>{{ $business->shopify_domain }}</p>
                        @if ($business->shopify_location_id)
                            <label class="small text-muted text-uppercase">Location</label>
                            <p class="mb-0">{{ $business->shopify_data['location']['name'] ?? $business->shopify_location_id }}</p>
                        @else
                            <label class="small text-muted text-uppercase mb-3">Location</label>
                            <p>
                                <a class="btn btn-primary btn-sm" href="{{ route('dashboard.business.integration.shopify.setting.location', $business->getKey()) }}">Click here to setup location</a>
                            </p>
                            <p class="small text-danger mb-0">
                                <i class="fas fa-exclamation-triangle mr-2"></i> You have to setup the location before you can sync the products.
                            </p>
                        @endif
                    </div>
                    <div class="card-body border-top p-4">
                        <a class="btn btn-primary" href="https://{{ $business->shopify_domain }}/admin">
                            <i class="fab fa-shopify mr-2"></i> Go to Shopify
                        </a>
                        <a class="btn btn-light" href="{{ route('dashboard.business.integration.shopify.setting.product', $business->getKey()) }}">
                            <i class="fas fa-sync mr-2"></i> Sync Products
                        </a>
                    </div>
                @else
                    <div class="card-body p-4">
                        <label class="small text-uppercase text-muted mb-3">Integrations</label>
                        <h2 class="text-primary mb-3 title">Shopify</h2>
                        <p>Install and Subscribe to ‘HitPay - Inventory Sync’ in the Shopify App Store to sync your Shopify store products to HitPay</p>
                        <a class="btn btn-primary" href="https://shopify.com">
                            <i class="fab fa-shopify mr-2"></i> Go to Shopify
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('body-stack')
    <script type="text/javascript">
        function showSpinner() {
            $('#removeShopifyButton').append($('<i class="fa fa-spinner fa-spin ml-2">'));
        }
    </script>
@endpush
