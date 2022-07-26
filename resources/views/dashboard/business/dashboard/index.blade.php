@extends('layouts.business', [
    'title' => 'Dashboard'
])

@section('business-content')
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-9 main-content">
            <business-dashboard
                business_id="{{ $business->getKey() }}"
                :business="{{ $business }}"
                :customisation="{{ $customisation }}"
                :data="{{ json_encode($data) }}"
                :refiner_survey_key="'{{ env('REFINER_ONBOARDING_SURVEY_KEY') }}'"
                shop_url="{{ $shop_url }}"/>
        </div>
    </div>
@endsection

@push('body-stack')
@endpush
