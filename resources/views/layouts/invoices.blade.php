@extends('layouts.root')

@section('invoices-header')
    @include('dashboard.business.invoice.blocks.header')
@endsection
@section('invoices-footer')
    @include('dashboard.business.invoice.blocks.footer')
@endsection

@section('root-content')
    <body>
        <div id="app" class="invoices">
            @yield('invoices-header')
            @yield('invoices-content')
            @yield('invoices-footer')
        </div>
        <script>
            window.HitPay = @json($hitpay_script_variables);
        </script>
        @stack('body-stack')
    </body>
@endsection
