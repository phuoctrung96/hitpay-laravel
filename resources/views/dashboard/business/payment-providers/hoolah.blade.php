@extends('dashboard.business.payment-providers.method', [
    'title' => 'Hoolah Settings'
])

@section('method-content')
  <hoolah-settings
    :provider="{{ json_encode($provider) }}"
    :banks_list="{{ json_encode($banks_list) }}"/>
@endsection
