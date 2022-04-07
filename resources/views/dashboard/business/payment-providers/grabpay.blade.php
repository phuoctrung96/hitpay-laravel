@extends('dashboard.business.payment-providers.method', [
    'title' => 'GrabPay Settings'
])

@section('method-content')
  <grabpay-settings
    :business="{{ json_encode($business) }}"
    :provider="{{ json_encode($provider) }}"
    :business_categories="{{ json_encode($business_categories) }}"/>
@endsection
