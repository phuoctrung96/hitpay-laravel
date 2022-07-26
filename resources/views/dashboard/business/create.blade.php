@php($title = 'New Business')

@extends('layouts.login')

@section('login-content')
  <business-create
    :email="'{{ $email }}'"
    :categories="{{ json_encode($business_categories)}}"
    :countries="{{ json_encode(array_values($countries->toArray())) }}"
    :src_url="{{ json_encode($src_url) }}"
    country="{{ $selectedCountry }}"
    recaptcha_sitekey="{{ env('GOOGLE_RECAPTCHA_SITEKEY') }}"
  />
@endsection
