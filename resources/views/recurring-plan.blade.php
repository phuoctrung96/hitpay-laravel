@extends('shop.layouts.app', [
    'title' => 'Recurring Billing ',
])

@php
    $customisation = $business->checkoutCustomisation();
    $logo = $business->logoUrl();
    $umami = config('checkout.umamiUrl');
@endphp

@section('root-content')
    <body>
    <div id="app" class="checkout-container ">
        <div id="demo" class="alert alert-warning rounded-0 border-top-0 border-left-0 border-right-0 mb-0 d-none">
            <div class="container text-center small">
                <strong>Demo in test mode.</strong> This app is running in test mode. You will not be charged.
            </div>
        </div>
        <recurring-billing-checkout
            :customisation="{{ $customisation }}"
            :business_image="'{{ $logo }}'"
            :business="{{ json_encode($business->getFilteredData()) }}"
            :recurring_plan = "{{json_encode($recurring_plan)}}"
            :expires_at = "'{{$recurring_plan->expires_at->format("d.m.Y")}}'"
        >
        </recurring-billing-checkout>
    </div>
    <script src="https://js.stripe.com/v3/"></script>
    <script type="text/javascript" defer>
        window.Name = '{{ $recurring_plan->customer_name ?? $recurring_plan->customer->name }}';
        window.HasPaymentMethod = {{ $recurring_plan->status === 'active' && $recurring_plan->payment_provider_payment_method_id ? 'true' : 'false' }};
        window.StripePublishableKey = '{{ $stripePublishableKey }}';
    </script>
    </body>
@endsection
