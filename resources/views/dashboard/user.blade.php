@php($user_name = $user->name)

@extends('layouts.app', [
    'title' => $user_name,
])

@section('app-content')
    @include('components.breadcrumb', [
        'breadcrumb_items' => $user_name,
    ])
    <div class="container pt-4 pb-5">
        <div class="row">
            <div class="col-md-9 col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <label class="small text-uppercase text-muted mb-3">Account</label>
                        <h2 class="text-primary mb-0 title">{{ $user_name }}</h2>
                    </div>
                    <user></user>
                    <div class="card-body p-4 border-top">
                        <p>We are currently don't allow users to change their emails on dashboard, to update your email, please contact us. To update your password, please go to security page. You can click the button below:</p>
                        <a class="btn btn-primary" href="{{ route('dashboard.user.security.home') }}"><i class="fas fa-lock mr-2"></i> Security</a>
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
