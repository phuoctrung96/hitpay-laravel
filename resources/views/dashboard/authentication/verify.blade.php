@php($title = 'Email Verification')

@extends('layouts.login', [
    'title' => $title,
])

@section('login-content')
    <authentication-validate-email />
@endsection

