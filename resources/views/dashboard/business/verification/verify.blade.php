@php($title = __('Account Verification'))

@extends('layouts.business')
@section('business-content')
    <div class="row">
        <div class="col-12 col-md-12 col-lg-9">
            <div class="card">
                <div class="row no-gutters">
                    @if($business->business_type == 'company')
                        <div class="col-12 col-md-12 d-flex border">
                            <div class="card-body text-center align-self-start">
                                <h6 class="text-primary font-weight-normal mb-5">Private Limited Companies And Sole Proprietors</h6>
                                <div class="px-md-3 mb-5">
                                    <img class="img-fluid" src="{{ asset('icons/myinfosg_color.svg') }}">
                                </div>
                                <a class="btn btn-sm" href="{{ route('dashboard.business.verification.redirect', [
                                'business_id' => $business->getKey(),
                                'type' => 'business',
                            ]) }}">
                                    <img class="img-fluid mb-2" src="{{ asset('icons/myinfo_business_button.svg') }}">
                                </a>
                                <p class="mb-5">I agree that I am a director/shareholder/ Sole proprietor</p>
                            </div>
                        </div>
                    @else
                        <div class="col-12 col-md-12 d-flex border">
                            <div class="card-body text-center align-self-start">
                                <h6 class="text-primary font-weight-normal mb-5">Individuals</h6>
                                <div class="px-md-3 mb-5">
                                    <img class="img-fluid" src="{{ asset('icons/singpass_color.png') }}">
                                </div>
                                <a class="btn btn-sm" href="{{ route('dashboard.business.verification.redirect', [
                                'business_id' => $business->getKey(),
                                'type' => 'personal',
                            ]) }}">
                                    <img class="img-fluid mb-2" src="{{ asset('icons/myinfo_button_i.png') }}">
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <p class="mt-5 mx-auto" style="width: 50%; text-align: center; border-bottom: 1px solid #000; line-height: 0.1em;margin: 10px 0 20px; ">
                <span style="background:#F3F5F8; padding:0 10px;">OR</span>
            </p>
            <a href="{{route('dashboard.business.verification.manual', $business->getKey())}}" class="btn btn-primary mt-5 mx-auto btn-block w-50">Enter Manually</a>
        </div>
    </div>
@endsection
