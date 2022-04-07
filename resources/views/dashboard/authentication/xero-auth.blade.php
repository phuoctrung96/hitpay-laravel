@extends('layouts.login', [
    'title' => __('Login'),
])

@section('login-content')
  <login-register-layout>
    <div class="row mb-5">
        <div class="mx-auto">
            <a href="{{ route('login') }}" class="btn btn-primary btn-block btn-lg py-3">Existing HitPay User</a>
            <a href="{{ route('auth.socialite', 'xero') }}" class="btn btn-secondary btn-block btn-lg py-3">
                <img src="/images/xero_logo.svg" style="height: 40px;" alt="">
                Sign up with Xero
            </a>
        </div>
    </div>
    <div class="{{ ($lighter_color_text ?? false) ? 'footer' : 'footer-dark' }}">
        <p class="small text-center">
            <a href="{{ route('home') }}"><i class="fas fa-home"></i> @lang('Home')</a>
        </p>
        <ul class="list-inline small text-center mb-0">
            <li class="list-inline-item">
                <a href="https://www.hitpayapp.com/termsofservice">@lang('Terms of Service')</a>
            </li>
        </ul>
    </div>
  </login-register-layout>
@endsection
