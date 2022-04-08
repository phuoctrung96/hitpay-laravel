@php($title = __('Account Verification'))
@extends('layouts.business')

@section('business-content')
    <div class="row">
        <div class="col-12 col-md-12 col-lg-9">
            <verification
            :fill_type="'manual'">
            </verification>
        </div>
    </div>
@endsection

@push('body-stack')
    <script>
        window.Business = @json($business);
        window.Type = "{{$type}}"
    </script>
@endpush
