
@extends('layouts.root')
@section('root-content')
    <body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-white border-bottom shadow-sm">
            <div class="container container-lg">
                <a class="nav-link" href="#">
                    <i class="fas fa-home"></i>
                </a>
                <div class="navbar-text mx-auto">
                    <img src="{{ asset('hitpay/logo-000036.png') }}" class="rounded" height="64" alt="{{ $business->getName() }}">
                </div>
            </div>
        </nav>
        @yield('products-content')
    </div>
    <script>
        window.HitPay = @json($hitpay_script_variables);
    </script>
    @stack('body-stack')
    </body>
@endsection
