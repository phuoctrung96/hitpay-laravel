@extends('shop.layouts.app', [
    'title' => 'Processing order ',
])

@section('root-content')
    <body>
        <div id="app">          
            <charge-wait
              business_id="{{ $business->id }}"
              charge_id="{{ $charge_id }}"
              />
        </div>
        <script>
            window.HitPay = @json($hitpay_script_variables);
        </script>
    </body>  
@endsection