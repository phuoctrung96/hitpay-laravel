@extends('layouts.card', [
    'title' => __('Login'),
])

@section('card-content')
    <main class="py-5 py-xs-6 py-sm-7">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 col-sm-8 col-md-6 col-lg-4 mx-auto">
                    <a href="{{ route('register') }}" class="btn btn-primary btn-block btn-lg py-3">Create Account</a>
                    <a href="{{ route('login') }}" class="btn btn-secondary btn-block btn-lg py-3">Login</a>
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
        </div>
    </main>
@endsection
