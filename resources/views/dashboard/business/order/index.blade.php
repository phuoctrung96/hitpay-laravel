@extends('layouts.business', [
    'title' => 'Orders'
])

@section('business-content')
    <div class="row justify-content-center">
        <business-order-list>
        </business-order-list>
        <div>
            <business-order-export></business-order-export>
            <business-delivery-export></business-delivery-export>
        </div>
    </div>
@endsection

@push('body-stack')
    <script>
        {{--window.Orders = @json($orders);--}}
        window.Statuses = @json($statuses);
        window.CurrentStatus = @json($currentStatus);
    </script>
@endpush
