@extends('layouts.business', [
    'title' => 'Store settings for ' . $business->name,
])

@section('business-content')
    <div class="row justify-content-center">
        <div class="col-md-12 col-lg-9 col-xl-8 main-content">
            <business-basic-detail></business-basic-detail>
        </div>
    </div>
@endsection

@push('body-stack')
    <script>
        window.Business = @json($business->toArray());
        window.Data = @json($data);
    </script>
@endpush
