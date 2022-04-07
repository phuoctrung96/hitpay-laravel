@php($title = isset($cusstomer) ? 'Edit Customer' : 'Add Customer')

@extends('layouts.business', [
    'title' => $title,
])

@section('business-content')
    <div class="row">
        <div class="col-12 col-md-9 col-lg-8 mb-4">
            <a href="{{ route('dashboard.business.customer.index', [
                $business->getKey(),
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to Customers</a>
        </div>
        <div class="col-12 col-md-9 col-lg-8 main-content">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <label class="small text-uppercase text-muted mb-3">Customers</label>
                    <h2 class="text-primary mb-0 title">{{ $title }}</h2>
                </div>
                <business-customer></business-customer>
                <div class="card-body border-top pt-0 pb-4">
                </div>
            </div>
        </div>
    </div>
@endsection

@push('body-stack')
    <script>
        window.Business = @json($business);
        window.Data = @json($data);
        @isset($customer)
            window.Customer = @json($customer)
        @else
            window.Customer = null;
        @endisset
    </script>
@endpush
