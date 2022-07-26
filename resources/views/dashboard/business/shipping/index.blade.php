@extends('layouts.business', [
    'title' => 'Shipping Settings'
])

@section('business-content')
    <div class="row justify-content-center">
        <business-shipping-list @if(session('success_message')) session_success = "{{ session('success_message') }}" @endif></business-shipping-list>
    </div>
@endsection

@push('body-stack')
    <script>
        window.Shippings = @json($shippings);
        window.Countries = @json($countries);
        window.ShippingDiscount = @json($shipping_discount);
        function check(trigger) {
            let current = new URL(window.location.href);
            let query = current.search;
            let params = new URLSearchParams(query);

            if ($(trigger).is(':checked')) {
                params.set('order_related_only', 1);
            } else {
                params.set('order_related_only', 0);
            }

            current.search = params.toString();
            window.location = current.toString();
        }
    </script>
@endpush
