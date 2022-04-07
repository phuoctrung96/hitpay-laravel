@extends('layouts.business', [
    'title' => $business->name,
])

@section('business-content')
    <div class="row justify-content-center">
        <div class="col-md-12 col-lg-9 col-xl-8 main-content">
            <shop-settings></shop-settings>
        </div>
    </div>
@endsection

@push('body-stack')
    <script>
        window.Business = @json($business->toArray());
        window.EnableDate = @json($enableDate);
        window.EnableTime = @json($enableTime);
        window.Data = @json($data);
    </script>
@endpush
