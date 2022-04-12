@php($title = __('Account Verification'))

@extends('layouts.business')
@section('business-content')
<div class="account-verification">
    <div class="option-verification">
        <div class="row">
            <div class="col-12 col-md-12 col-lg-9">
                <div class="card">
                    <div class="card-body">
                        <h3>Account verification</h3>
                        <div class="excerpt">
                            <p>Account verification is required.</br>
                            </p>
                        </div>
                        @if(in_array($business->business_type, ['company', 'partner']))
                        <div class="d-flex border mb-4">
                            <div class="card-body p-4 clearfix">
                                <div class="is-checkbox">
                                    <label class="label-checkbox">
                                        <input type="radio" id="singpass_business" value="singpass_business" name="type-verify" checked onclick="handleClick(this);">
                                        <span class="checkmark"></span>
                                    </label>
                                </div>
                                <div class="is-text">
                                    <div class="img-title">
                                        <img class="img-singpass" src="{{ asset('icons/singpass_color.svg') }}">
                                    </div>
                                    <p>Singpass enables you to retrieve your </br>
                                    personal data from relevant government </br>
                                    agencies to pre-fill the relevant fields, making <br>
                                    digital transaction faster and more convenient. <br>
                                    Verification will be instant.
                                    </p>
                                    <p><img class="img-fluid mb-2" src="{{ asset('icons/myinfo_singpass_color.svg') }}"></p>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="d-flex border mb-4">
                            <!-- <div class="card-body text-center align-self-start">
                                <h6 class="text-primary font-weight-normal mb-5">Individuals</h6>
                                <div class="px-md-3 mb-5">
                                    <img class="img-fluid" src="{{ asset('icons/singpass_color.png') }}">
                                </div>
                                <a class="btn btn-sm" href="{{ route('dashboard.business.verification.redirect', ['business_id' => $business->getKey(),
                                'type' => 'personal',
                            ]) }}">
                                    <img class="img-fluid mb-2" src="{{ asset('icons/myinfo_button_i.png') }}">
                                </a>
                            </div> -->
                            <div class="card-body p-4 clearfix">
                                <div class="is-checkbox">
                                    <label class="label-checkbox">
                                        <input type="radio" id="singpass_individuals" value="singpass_individuals" name="type-verify" checked onclick="handleClick(this);">
                                        <span class="checkmark"></span>
                                    </label>
                                </div>
                                <div class="is-text">
                                    <div class="img-title">
                                            <img class="img-singpass" src="{{ asset('icons/singpass_color.svg') }}">
                                        </div>
                                        <p>Singpass enables you to retrieve your </br>
                                        personal data from relevant government </br>
                                        agencies to pre-fill the relevant fields, making <br>
                                        digital transaction faster and more convenient. <br>
                                        Verification will be instant.
                                        </p>
                                        <p><img class="img-fluid mb-2" src="{{ asset('icons/myinfo_singpass_color.svg') }}"></p>
                                </div>
                            </div>
                        </div>
                        @endif
                        <div class="d-flex border mb-4">
                            <div class="card-body p-4 clearfix">
                                <div class="is-checkbox">
                                    <label class="label-checkbox">
                                        <input type="radio" id="manual" value="manual" name="type-verify" onclick="handleClick(this);">
                                        <span class="checkmark"></span>
                                    </label>
                                </div>
                                <div class="is-text">
                                    <p>Enter form Manually</p>
                                    <p>Filling out the form may take some time <br>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="is-btn-group d-block text-center pt-3">
                            <a href="#" id="continue" class="btn btn-primary">Continue</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>   
    
@endsection
@push('body-stack')
    <script>
        let url_singpass_business = "{{ route('dashboard.business.verification.redirect', ['business_id' => $business->getKey(),'type' => 'business',]) }}";
        let url_singpass_individuals = "{{ route('dashboard.business.verification.redirect', ['business_id' => $business->getKey(),'type' => 'personal',]) }}";
        let url_manual = "{{route('dashboard.business.verification.manual', $business->getKey())}}";

        if(document.getElementById("singpass_business")) {
            document.getElementById("continue").href = url_singpass_business; 
        }else if(document.getElementById("singpass_individuals")) {
            document.getElementById("continue").href = url_singpass_individuals;
        }else{
            document.getElementById("continue").href = url_singpass_business; 
        }

        function handleClick(myRadio) {
            let redirect_url = "";
            if(myRadio.value == "singpass_business"){
                redirect_url = url_singpass_business;
            } else if(myRadio.value == "singpass_individuals"){
                redirect_url = url_singpass_individuals;
            } else {
                redirect_url = url_manual;
            }

            document.getElementById("continue").href= redirect_url; 
            return false;
        }

    </script>
@endpush
