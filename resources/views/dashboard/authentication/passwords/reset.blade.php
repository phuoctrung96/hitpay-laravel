@extends('layouts.card', [
    'title' => __('Reset Password'),
])

@section('card-content')
    <main class="py-5 py-xs-6 py-sm-7">
        <div class="container">
            <div class="card width-sm border-0 shadow-sm mx-auto mb-5 mb-xs-6">
                <div class="card-body px-xs-4 py-5 py-md-6">
                    <h1 class="h6 text-center mb-5">@lang('Reset password to continue')</h1>
                    <authentication-password-reset></authentication-password-reset>
                    <p class="text-center small mb-0 mt-5">
                        <a href="{{ route('login') }}">@lang('Login instead')</a>
                    </p>
                </div>
            </div>
        </div>
    </main>
@endsection
