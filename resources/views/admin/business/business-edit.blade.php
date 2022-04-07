@extends('layouts.admin', [
    'title' => 'Edit Business'
])

@php
    if ($business->verified_wit_my_info_sg && ($verification = $business->verifications()->latest()->first())) {
        $verificationData = $verification->status === \App\Enumerations\VerificationStatus::VERIFIED ? json_encode($verification->my_info_data, 128) : json_encode($verification->submitted_data, 128);
    }
@endphp

@section('admin-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 main-content">
            <div class="col-12 col-md-9 col-lg-8 mb-4">
                <a href="{{ route('admin.business.show', $business->getKey()) }}">
                    <i class="fas fa-reply fa-fw mr-3"></i> Back
                </a>
            </div>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <h2 class="text-primary mb-3 title">{{ $business->getName() }}</h2>
                    <div class="media">
                        <div class="media-body">
                            <p class="text-dark small mb-2">
                                <span class="text-muted"># {{ $business->getKey() }}</span></p>
                        </div>
                    </div>
                </div>
                <div class="card-body px-4 py-0">
                    <p class="font-weight-bold mb-4">Edit Information</p>
                </div>
                <form method="post" action="{{ route('admin.business.update', $business->getKey()) }}">
                    @csrf
                    @method('put')
                    <div class="card-body bg-light p-4 border-top">
                        <div class="form-group">
                            <label for="owner_email_input">Owner Email (Login Email)</label>
                            <input id="owner_email_input" class="form-control {{ $errors->has('owner_email') ? 'is-invalid' : '' }}" title="owner Email" name="owner_email" value="{{ old('owner_email', $business->owner->email) }}">
                            @error('owner_email')
                            <span class="text-danger small" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="business_email_input">Business Email</label>
                            <input id="business_email_input" class="form-control {{ $errors->has('business_email') ? 'is-invalid' : '' }}" title="Business Email" name="business_email" value="{{ old('business_email', $business->email) }}">
                            @error('business_email')
                            <span class="text-danger small" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="business_phone_number_input">Business Phone Number</label>
                            <input id="business_phone_number_input" class="form-control {{ $errors->has('business_phone_number') ? 'is-invalid' : '' }}" title="Business Phone Number" name="business_phone_number" value="{{ old('business_phone_number', $business->phone_number) }}">
                            @error('business_phone_number')
                            <span class="text-danger small" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        @if (isset($verificationData))
                            <div class="form-group mb-0">
                                <label for="business_verification">Business Verification</label>
                                <textarea id="business_verification" rows=10 class="form-control {{ $errors->has('business_verification') ? 'is-invalid' : '' }}" title="Business Verification" name="business_verification">{{$verificationData}}</textarea>
                                @error('business_verification')
                                <span class="text-danger small" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        @endif
                    </div>
                    <div class="card-body border-top p-4">
                        <button class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i> Save
                        </button>
                        <a class="btn btn-secondary" href="{{ route('admin.business.show', $business->getKey()) }}">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
