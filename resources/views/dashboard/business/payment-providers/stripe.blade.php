@extends('dashboard.business.payment-providers.method', [
    'title' => 'Stripe Settings'
])

@section('method-content')
  <stripe-settings
    :provider="{{ json_encode($provider) }}"
    :user="{{json_encode(Auth::user()->load('businessUsers'))}}"/>
@endsection
