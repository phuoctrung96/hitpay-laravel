@extends('layouts.admin', [
    'title' => 'Onboarding'
])

@section('admin-content')
  <admin-onboarding-index
    :data="{{ json_encode($data) }}"/>
@endsection
