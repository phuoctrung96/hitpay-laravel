@php($customisation = $business->checkoutCustomisation())

@extends('shop.layouts.app', [
    'title' => 'Shop is offline',
])

@section('app-content')
    <div class="row">
            <div class="col-md-12">
                <div class="align-self-center text-center">
                    <h3 class="mb-3 ">Shop is offline</h3>
                </div>
            </div>
        </div>
@endsection
