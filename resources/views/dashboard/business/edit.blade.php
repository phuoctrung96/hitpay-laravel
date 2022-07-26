@extends('layouts.business', [
    'title' => 'Store settings for ' . $business->name,
])

@section('business-content')
    <div class="row justify-content-center">
        <div class="col-md-12 col-lg-9 col-xl-8 main-content">
            <business-basic-detail business_id="{{ $business->getKey() }}"></business-basic-detail>
        </div>
    </div>
@endsection
