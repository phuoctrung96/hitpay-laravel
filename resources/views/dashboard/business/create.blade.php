@php($title = 'New Business')

@extends('layouts.login')

@section('login-content')
  <business-create
    :email="'{{ $email }}'"
    :categories="{{ json_encode($business_categories)}}"
    :countries="{{ json_encode($countries) }}"
    :src_url="{{ json_encode($src_url) }}"
    country="{{ $selectedCountry }}"
  />
@endsection
