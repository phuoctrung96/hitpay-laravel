@extends('layouts.login', [
    'title' => __('Checkpoint'),
])

@section('login-content')
  <login-register-layout>
    <div class="card width-sm border-0 shadow-sm mx-auto mb-5 mb-xs-6">
        <div class="card-body px-xs-4 py-5 py-md-6">
            <h1 class="h5 text-center mb-5">@lang('Enter your OTP to continue')</h1>
            <authentication-checkpoint
              token="{{ $token }}"/>
            <p class="text-center small mb-0 mt-3">Lost your device? Email to: <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a></p>
        </div>
    </div>
    <div class="{{ ($lighter_color_text ?? false) ? 'footer' : 'footer-dark' }}">
        <p class="small text-center">
            <a href="{{ route('home') }}"><i class="fas fa-home"></i> @lang('Home')</a>
        </p>
        <ul class="list-inline small text-center mb-0">
            <li class="list-inline-item">
                <a href="{{ url('terms-of-service') }}">@lang('Terms of Service')</a>
            </li>
            <li class="list-inline-item">
                <a href="{{ url('privacy-policy') }}">@lang('Privacy Policy')</a>
            </li>
        </ul>
    </div>
  </login-register-layout>
@endsection
