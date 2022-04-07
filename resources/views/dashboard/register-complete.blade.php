@php($title = 'Register')

@extends('layouts.login')

@section('login-content')
  <authentication-register-complete
      hash="{{$hash}}"
      email="{{$email}}"/>
@endsection
