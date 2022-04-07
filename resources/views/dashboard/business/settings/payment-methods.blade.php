@extends('layouts.business', [
    'title' => 'Payment methods'
])

@section('business-content')
<payment-methods
    :user="{{json_encode(Auth::user()->load('businessUsers'))}}"
  :business="{{ json_encode($business) }}"/>
@endsection
