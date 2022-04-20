@php($title = 'Register')

@extends('layouts.login')

@section('login-content')
  <authentication-register
    referral="{{$partnerReferral}}"
    business_referral="{{$businessReferral}}"
    name="{{optional($userData)['name']}}"
    email="{{optional($userData)['email']}}"/>
@endsection
