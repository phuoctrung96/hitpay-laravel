@php($title = __('Two-Factor Authentication'))
@php($isAuthenticationSecretEnabled = $user->isAuthenticationSecretEnabled())
@php($breadcrumb_items[] = [
    'url' => route('dashboard.user.security.secret'),
    'name' => $title,
])

@if (!$isAuthenticationSecretEnabled)
    @php($breadcrumb_items[] = __('Setup'))
@else
    @php($breadcrumb_items[] = __('Reset'))
@endif

@extends('layouts.app')

@section('app-content')
    @include('components.breadcrumb', [
        'breadcrumb_items' => array_merge([
            [
                'name' => $user->name,
                'url' => route('dashboard.user.profile'),
            ],
            [
                'url' => route('dashboard.user.security.home'),
                'name' => __('Security'),
            ],
        ], $breadcrumb_items),
    ])
    <main class="bg-light bg-hypnotize pt-4 pt-xs-5 pt-sm-6 pb-5 pb-xs-6 pb-sm-7">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-9 col-xl-8">
                    <div class="card shadow">
                        <div class="card-body {{ $isAuthenticationSecretEnabled ? 'pt-4 px-xs-4 pt-sm-5 px-sm-5 pt-md-6 px-md-6 pb-3' : 'py-4 px-xs-4 p-sm-5 p-md-6' }}">
                            <h1 class="card-title h4 font-weight-bold mb-3">{{ $title }}</h1>
                            <p class="card-text">@lang('With two-factor authentication enabled, the only way someone can sign into your account is if they know both your password and have access to the authentication code on your phone.')
                            <p class="card-text">@lang('Add an application such as Google Authenticator or Microsoft Authenticator to get two-factor authentication codes when prompted.')</p>
                            @if (!$isAuthenticationSecretEnabled)
                                <a class="btn btn-success" href="#" data-toggle="modal" data-target="#enablingModal">
                                    @lang('Set up using an app')
                                </a>
                            @else
                                <a class="font-weight-bold text-danger" href="#" data-toggle="modal" data-target="#disablingModal">
                                    @lang('Disable two-factor authentication')
                                </a>
                                <authentication-secret-disabling></authentication-secret-disabling>
                            @endif
                        </div>
                        @if ($isAuthenticationSecretEnabled)
                            <div class="alert alert-danger border-y rounded-0 p-card-cap px-xs-4 px-sm-5 px-md-6 mb-3">
                                <i class="fas fa-exclamation-triangle"></i> @lang('You\'re about to change your two-factor authentication device. This will invalidate your current two-factor authentication devices and recovery codes. ')
                            </div>
                            <div class="card-body pt-0 pb-xs-4 px-xs-4 pb-sm-5 px-sm-5 pb-md-6 px-md-6">
                                <a class="btn btn-success" href="#" data-toggle="modal" data-target="#enablingModal">
                                    @lang('Reset using an app')
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </main>
    <authentication-secret-enabling></authentication-secret-enabling>
@endsection
