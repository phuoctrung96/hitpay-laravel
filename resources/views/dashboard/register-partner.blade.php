@php($title = 'Register')

@extends('layouts.login')

@section('login-content')
  <authentication-register-partner
      :categories="{{json_encode($business_categories)}}"
      :countries="{{json_encode($countries)}}"
      recaptcha_sitekey="{{ env('GOOGLE_RECAPTCHA_SITEKEY') }}"
  />
@endsection
