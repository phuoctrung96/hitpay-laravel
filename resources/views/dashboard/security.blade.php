@php($title = __('Security'))

@extends('layouts.app')

@section('app-content')
    @include('components.breadcrumb', [
        'breadcrumb_items' => [
            [
                'name' => $user->name,
                'url' => route('dashboard.user.profile'),
            ],
            $title,
        ],
    ])
    <div class="container pt-4 pb-5">
        <div class="row">
            <div class="col-md-9 col-lg-8">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <label class="small text-uppercase text-muted mb-3">Account</label>
                        <h2 class="text-primary mb-0 title">{{ $title }}</h2>
                        <p class="card-text">@lang('At :app_name, we take online security seriously. Use tools below to help keep your :app_name account safer, make it easier to recover if it\'s compromised, and strengthen it against attacks.', [
                            'app_name' => $app_name,
                        ])</p>
                    </div>
{{--                    <a class="hoverable" href="{{ route('dashboard.user.security.email.edit') }}">--}}
{{--                        <div class="card-body bg-light p-4 border-top">--}}
{{--                            <div class="media">--}}
{{--                                <i class="fas fa-envelope fa-fw fa-2x text-body align-self-center mr-card mr-xs-4 mr-sm-5 mr-md-6"></i>--}}
{{--                                <div class="media-body align-self-center">--}}
{{--                                    <span class="font-weight-bold d-inline-block">@lang('Change your login email')</span>--}}
{{--                                    <p class="text-body mb-0">@lang('It\'s a good idea to use a strong password that you\'re not using elsewhere.')</p>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </a>--}}
                    <a class="hoverable" href="{{ route('dashboard.user.security.password.edit') }}">
                        <div class="card-body bg-light p-4 border-top">
                            <div class="media">
                                <i class="fas fa-key fa-fw fa-2x text-body align-self-center mr-card mr-xs-4 mr-sm-5 mr-md-6"></i>
                                <div class="media-body align-self-center">
                                    <span class="font-weight-bold d-inline-block">@lang('Change your password')</span>
                                    <p class="text-body mb-0">@lang('It\'s a good idea to use a strong password that you\'re not using elsewhere.')</p>
                                    @if (isset($user->password_updated_at))
                                        <small class="small text-muted mb-0">@lang('Last changed on :datetime.', [
                                            'datetime' => $user->password_updated_at->toDayDateTimeString(),
                                        ])</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                    <a class="hoverable" href="{{ route('dashboard.user.security.secret') }}">
                        <div class="card-body bg-light p-4 border-top">
                            <div class="media">
                                <i class="fas fa-shield-alt fa-fw fa-2x text-body align-self-center mr-card mr-xs-4 mr-sm-5 mr-md-6"></i>
                                <div class="media-body align-self-center">
                                    @if ($user->isAuthenticationSecretEnabled())
                                        <span class="font-weight-bold d-inline-block">@lang('Review two-factor authentication')</span>
                                        <p class="text-body mb-0">@lang('Reset your two-factor authentication device, or view recovery codes.')</p>
                                        <span class="badge badge-success">@lang('Enabled')</span>
                                    @else
                                        <span class="font-weight-bold d-inline-block">@lang('Set up two-factor authentication')</span>
                                        <p class="text-body mb-0">@lang('Log in with an authentication code from your phone as well as a password.')</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
{{--                    <a class="hoverable" href="{{ route('dashboard.user.security.session') }}">--}}
{{--                        <div class="card-body bg-light p-4 border-top">--}}
{{--                            <div class="media">--}}
{{--                                <i class="fas fa-laptop fa-fw fa-2x text-body align-self-center mr-card mr-xs-4 mr-sm-5 mr-md-6"></i>--}}
{{--                                <div class="media-body align-self-center">--}}
{{--                                    <span class="font-weight-bold d-inline-block">@lang('Manage your sessions')</span>--}}
{{--                                    <p class="text-body mb-0">@lang('See your active sessions, and sign out if you\'d like.')</p>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                    </a>--}}
                    <div class="card-body pt-0 pb-4 border-top">
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
