@extends('layouts.root')

@section('root-content')
  <body>
    <div id="app">
      <div>
        @yield('app-content')
      </div>
    </div>

    <script>
      window.HitPay = @json($hitpay_script_variables);
      //Set your APP_ID
  
    </script>

    @stack('body-stack')
  </body>
@endsection
