@extends('layouts.business', [
    'title' => 'Dashboard'
])

@section('business-content')
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-10 main-content">
            <business-insight
                business_id="{{ $business->getKey() }}"
                :refiner_insights_survey_key="'{{ env('REFINER_INSIGHTS_SURVEY_KEY') }}'"/>
        </div>
    </div>
@endsection

@push('body-stack')
@endpush
