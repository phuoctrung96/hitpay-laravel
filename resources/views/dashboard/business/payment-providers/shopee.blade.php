@extends('dashboard.business.payment-providers.method', [
    'title' => 'Shopee Settings'
])

@section('method-content')
  <shopee-settings
    :business="{{ json_encode($business) }}"
    :provider="{{ json_encode($provider) }}"
    :uen="{{ json_encode($uen) }}"
    :mcc="{{ json_encode($mcc) }}"
    :verification="{{ json_encode($verification) }}"/>
@endsection
