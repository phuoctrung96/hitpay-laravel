@extends('dashboard.business.payment-providers.method', [
    'title' => 'Shopee Settings'
])

@section('method-content')
  <shopee-settings
    :provider="{{ json_encode($provider) }}"
    :uen="{{ json_encode($uen) }}"
    :mcc="{{ json_encode($mcc) }}"
    :verification="{{ json_encode($verification) }}"
    :business_categories="{{ json_encode($business_categories) }}"/>
@endsection
