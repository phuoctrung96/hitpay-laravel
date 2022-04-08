@php($title = __('Account Verification'))
@extends('layouts.business')

@section('business-content')
    <div class="row">
        <div class="col-12 col-md-12 col-lg-9">
            <h5 class="text-center">{{ $verificationStatusTitle }}</h5>

            @if($isOwner)

                @if(session('success_message'))
                    <div class="alert alert-success border-left-0 border-right-0 rounded-0 alert-dismissible fade show" role="alert">
                        {{ session('success_message') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                <verification-cognito-show
                    :fill_type="'{{ $verificationStatus }}'"
                    :verification_id="'{{$verification->id}}'"
                >
                </verification-cognito-show>
            @else
                <p>Please ask the owner "<b>{{ $businessUserOwner->email }}</b>" to complete the verification</p>
            @endif
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
