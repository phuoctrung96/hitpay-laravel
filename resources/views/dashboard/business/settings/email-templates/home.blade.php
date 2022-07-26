@extends('layouts.business', [
    'title' => 'Email Templates'
])

@section('business-content')
    <email-template business="{{ $business }}" ></email-template>
@endsection
