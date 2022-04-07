@php($user_name = $user->name)

@extends('layouts.app', [
    'title' => $user_name,
])

@section('app-content')
    @include('components.breadcrumb', [
        'breadcrumb_items' => 'Create New HitPay Login',
    ])
    <div class="container pt-4 pb-5">
        <div class="row">
            <div class="col-12 col-sm-8 col-md-6 col-lg-4 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="text-primary mb-4 title">We have updated our platform to introduce a whole new set of business tools to power your business</h4>
                        <ul class="list-unstyled">
                            <li class="mb-3">HitPay Terminal</li>
                            <li class="mb-3">HitPay E-Commerce Payment Gateway</li>
                            <li class="mb-3">HitPay Invoicing</li>
                            <li class="mb-3">HitPay E-commerce Platform</li>
                            <li class="mb-3">HitPay Instagram Checkouts</li>
                            <li class="mb-3">Shopify & Xero Integrations</li>
                            <li class="mb-3">And whole lot more…</li>
                        </ul>
                        <a href="{{ route('dashboard.user.profile') }}" class="btn btn-success btn-block">Next</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('body-stack')
    <script>
        window.User = @json($user);
    </script>
@endpush
