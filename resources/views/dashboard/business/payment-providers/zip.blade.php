@extends('dashboard.business.payment-providers.method', [
    'title' => 'Zip Settings'
])

@section('method-content')
  <zip-settings
    :provider="{{ json_encode($provider) }}"
    :uen="{{ json_encode($uen) }}"
    :mcc="{{ json_encode($mcc) }}"
    :verification="{{ json_encode($verification) }}"
    :business_categories="{{ json_encode($business_categories) }}"/>
@endsection
