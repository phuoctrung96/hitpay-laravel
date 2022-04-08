@php($title = 'Register')

@extends('layouts.login')

@section('login-content')
  <authentication-register-partner
      :categories="{{json_encode($business_categories)}}"
  />
@endsection
