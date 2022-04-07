@php($title = __('Account Verification'))
@extends('layouts.business')

@section('business-content')
    <div class="row">
        <div class="col-12 col-md-12 col-lg-9">
            <verification
                :fill_type="'myinfo'"
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
        window.IsMoreConfirm = @json($isMoreConfirm);
    </script>
@endpush
