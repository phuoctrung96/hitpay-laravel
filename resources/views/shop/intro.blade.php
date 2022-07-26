@extends('shop.layouts.app', [
    'title' =>'Home',
    'app_name' => $business->getName(),
])

@php
    $customisation = $business->getStoreCustomisationStyles();
@endphp

@push('head-stack')
    <style>
        .btn {
            border-color: {{$customisation['button_color']}};
            color: black;
        }

        .btn:hover {
            background-color: {{$customisation['button_color']}};
            color: {{$customisation['button_text_color']}};
        }
    </style>
@endpush

@section('app-content')
    <div class="container container-lg mb-3 mt-5">
        <h2>About Us</h2>
        <p>{!!$business->introduction!!}</p>
    </div>
@endsection

@push('body-stack')

@endpush
