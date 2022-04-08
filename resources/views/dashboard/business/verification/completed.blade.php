@php($title = __('Account Verification'))
@extends('layouts.business')

@section('business-content')
    <div class="row">
        <div class="col-12 col-md-12 col-lg-9">
            <h5 class="text-center">{{$verification->status == \App\Enumerations\VerificationStatus::MANUAL_VERIFIED || $verification->status == \App\Enumerations\VerificationStatus::VERIFIED ? 'Verification Completed' : 'Verification Submitted'}}</h5>
            @if(session('success_message'))
                <div class="alert alert-success border-left-0 border-right-0 rounded-0 alert-dismissible fade show" role="alert">
                    {{ session('success_message') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif
            <verification
                :fill_type="'completed'"
                :verification_id="'{{$verification->id}}'"
            >
            </verification>
        </div>
    </div>
@endsection
@push('body-stack')
    <script>
        window.Business = @json($business);
        window.Verification = @json($verification_data);
        window.Type = @json($type);
    </script>
@endpush
