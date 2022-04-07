@extends('layouts.root')

@section('root-content')
  <body>
    <div id="app">
      @yield('login-content')
    </div>
    <script>
        window.HitPay = @json($hitpay_script_variables);
    </script>
  </body>
@endsection
