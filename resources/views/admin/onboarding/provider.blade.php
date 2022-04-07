@extends('layouts.admin', [
    'title' => 'Onboarding'
])

@section('admin-content')
  <admin-onboarding-provider
    provider="{{ $provider }}"
    :initial_data="{{ json_encode($initialData) }}"
    />
@endsection
