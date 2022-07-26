@extends('layouts.business', [
    'title' => 'OAuth Clients'
])

@section('business-content')
    <div class="row justify-content-center">
        <div class="col-md-12 col-lg-12 main-content">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <h2 class="text-primary mb-0 title">OAuth Clients</h2>
                </div>
                <passport-clients business_id="{{ $business->getKey() }}"></passport-clients>
            </div>
            <business-help-guide :page_type="'client_keys'"></business-help-guide>
        </div>
    </div>
@endsection

@push('body-stack')
    <script>
        window.StripePublishableKey = '{{ $stripePublishableKey }}';
    </script>
@endpush
