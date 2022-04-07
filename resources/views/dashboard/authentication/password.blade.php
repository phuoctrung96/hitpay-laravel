@php($title = 'Update Password')

@extends('layouts.app', [
    'title' => $title,
])

@section('app-content')
    @include('components.breadcrumb', [
        'breadcrumb_items' => [
            [
                'name' => $user->name,
                'url' => route('dashboard.user.profile'),
            ],
            [
                'name' => 'Security',
                'url' => route('dashboard.user.security.home'),
            ],
            $title,
        ],
    ])
    <div class="container pt-4 pb-5">
        <div class="row">
            <div class="col-md-9 col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <label class="small text-uppercase text-muted mb-3">Account</label>
                        <h2 class="text-primary mb-0 title">{{ $title }}</h2>
                    </div>
                    <authentication-password-update></authentication-password-update>
                    <div class="card-body pt-0 pb-4 border-top">
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
