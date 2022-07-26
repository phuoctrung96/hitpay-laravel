@php($title = isset($shipping) ? 'Edit Shipping Method' : 'Add Shipping Method')

@extends('layouts.business', [
    'title' => $title,
])

@section('business-content')
    <div class="row">
        <div class="col-12 col-md-9 col-lg-8 mb-4">
            <a href="{{ route('dashboard.business.setting.shipping.home', [
                $business->getKey(),
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to Shipping</a>
        </div>
        <div class="col-12 col-md-9 col-lg-8 main-content">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <label class="small text-uppercase text-muted mb-3">Setttings</label>
                    <h2 class="text-primary mb-3 title">{{ $title }}</h2>
                    <p class="mb-0">The shipping method below will be applicable to all shippable products</p>
                </div>
                <business-shipping></business-shipping>
            </div>
        </div>
    </div>
@endsection

@push('body-stack')
    <script>
        window.Data = @json($data);
        @isset($shipping)
            window.Shipping = @json($shipping)
        @else
            window.Shipping = null;
        @endisset
    </script>
@endpush
