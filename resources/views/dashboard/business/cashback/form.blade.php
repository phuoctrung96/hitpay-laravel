@php($title = isset($cashback) ? 'Edit Cashback' : 'Add Cashback')

@extends('layouts.business', [
    'title' => $title,
])

@section('business-content')
    <div class="row">
        <div class="col-12 col-md-9 col-lg-8 mb-4">
            <a href="{{ route('dashboard.business.cashback.index', [
                $business->getKey(),
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to Cashbacks</a>
        </div>
        <div class="col-12">
            <business-cashback-create-edit></business-cashback-create-edit>
        </div>
    </div>
@endsection

@push('body-stack')
    <script>
        window.Business = @json($business);
        window.Channels = @json($channels);
        window.PaymentMethods = @json($paymentMethods);
        window.Fees = @json($fees);
        @isset($cashback)
            window.Cashback = @json($cashback)
            @else
            window.Cashback = null;
        @endisset
    </script>
@endpush
