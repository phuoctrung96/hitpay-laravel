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
            <div class="col-md-9 col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <label class="small text-uppercase text-muted mb-3">Account</label>
                        <h2 class="text-primary mb-3 title">Create New HitPay Login</h2>
                        <p>This is <span class="font-weight-bold">mandatory</span> and you will login to HitPay on mobile and desktop using the newly created credentials.</p>
                        <p><span class="font-weight-bold">You will no longer login to HitPay using Stripe details</span> and this has no impact on your old transaction data on HitPay</p>
                    </div>
                    <user-setup></user-setup>
                    <div class="card-body pt-4 pb-0 border-top">
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
