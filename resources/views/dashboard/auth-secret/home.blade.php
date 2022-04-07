@php($page_title = __('Two-Factor Authentication'))
@php($breadcrumb_items = $page_title)

@extends('layouts.app')

@section('app-content')
    @include('account.components.breadcrumb', [
        'breadcrumb_items' => array_merge([
            [
                'url' => route('security'),
                'name' => __('Security'),
            ],
        ], isset($breadcrumb_items) ? (array) $breadcrumb_items : []),
    ])
    <main class="bg-light bg-hypnotize pt-4 pt-xs-5 pt-sm-6 pb-5 pb-xs-6 pb-sm-7">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-9 col-xl-8">
                    <div class="card shadow">
                        <div class="card-body py-4 px-xs-4 p-sm-5 p-md-6">
                            <h1 class="card-title h4 font-weight-bold mb-3">{{ $page_title }}</h1>
                            <p class="card-text">@lang('Two-factor authentication is an extra layer of security used when logging into websites or apps. By enabling two-factor authentication, you have to log in with your email and password and provide another form of authentication that only you know or have access to.')</p>
                            <a class="font-weight-bold text-danger" href="#" data-toggle="modal" data-target="#disablingModal">
                                @lang('Disable two-factor authentication')
                            </a>
                            <account-auth-secret-disabling-component></account-auth-secret-disabling-component>
                        </div>
                        <div class="bg-light p-card-cap px-xs-4 px-sm-5 px-md-6 border-card border-y">@lang('Two-factor method')</div>
                        <a href="{{ route('auth-secret.setup') }}">
                            <div class="media p-card-body p-xs-4 px-sm-5 px-md-6 border-card clickable">
                                <i class="fas fa-qrcode fa-fw fa-2x text-body align-self-center mr-card mr-xs-4 mr-sm-5 mr-md-6"></i>
                                <div class="media-body align-self-center">
                                    <span class="font-weight-bold d-inline-block">@lang('Authentication App')</span>
                                    <p class="text-body mb-0">@lang('You\'ll receive a code via an authentication app.')</p>
                                </div>
                            </div>
                        </a>
                        <div class="bg-light p-card-cap px-xs-4 px-sm-5 px-md-6 border-card border-y">@lang('Recovery options')</div>
                        <a href="#" data-toggle="modal" data-target="#recoveryCodesModal">
                            <div class="media p-card-body p-xs-4 px-sm-5 px-md-6 border-card clickable">
                                <i class="fas fa-file-alt fa-fw fa-2x text-body align-self-center mr-card mr-xs-4 mr-sm-5 mr-md-6"></i>
                                <div class="media-body align-self-center">
                                    <span class="font-weight-bold d-inline-block">@lang('Recovery Codes')</span>
                                    <p class="text-body mb-0">@lang('Recovery codes can be used to access your account in the event you lose access to your device and cannot receive two-factor authentication codes.')</p>
                                </div>
                            </div>
                        </a>
                        <account-recovery-code-set-component></account-recovery-code-set-component>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection
