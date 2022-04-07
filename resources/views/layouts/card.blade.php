@php($user = Auth::user())

@extends('layouts.root')

@section('root-content')
    <body class="bg-light-primary">
    <div id="app">
        <nav class="navbar navbar-main navbar-expand navbar-light bg-white border-bottom-bold border-primary py-3">
            <div class="container">
                <a class="navbar-brand mx-auto" href="https://www.hitpayapp.com">
                    <img src="{{ asset('hitpay/logo-000036.png') }}" height="30" alt="{{ $app_name }}">
                </a>
            </div>
        </nav>
        @yield('card-content')
    </div>
    <script>
        window.HitPay = @json($hitpay_script_variables);
    </script>
    @stack('body-stack')
    </body>
@endsection
