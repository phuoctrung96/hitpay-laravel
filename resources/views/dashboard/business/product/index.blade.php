@extends('layouts.business', [
    'title' => 'Products'
])
@php($setupPayment = isset($business) && ($business->paymentProviders()->whereIn('payment_provider', [
    $business->payment_provider,
    \App\Enumerations\PaymentProvider::DBS_SINGAPORE,
])->count() === 0))
@section('business-content')
    <div class="row">
        <div class="col-md-10 col-lg-10 main-content">
            @if (isset($setupPayment) && $setupPayment)
                <div class="alert alert-danger ">
                    <div class="container-fluid text-center">
                        <p class="small mb-0">You can't create <products></products> without enabling payment method.
                            <a href="{{ route('dashboard.business.payment-provider.home', $business->getKey()) }}">Enable now</a>
                        </p>
                    </div>
                </div>
            @else
                <business-product-dashboard :current_user="{{ json_encode($currentBusinessUser) }}"></business-product-dashboard>
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
        window.Data = @json($data);
    </script>
@endpush
