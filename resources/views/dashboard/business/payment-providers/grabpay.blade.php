@extends('dashboard.business.payment-providers.method', [
    'title' => 'GrabPay Settings'
])

@section('method-content')
  <grabpay-settings
    :provider="{{ json_encode($provider) }}"
    :business_categories="{{ json_encode($business_categories) }}"/>
@endsection
