@extends('layouts.login', [
    'title' => __('Login'),
])

@section('login-content')
  <authentication-login :login_form_data="{{ json_encode($form_data) }}"/>
@endsection
