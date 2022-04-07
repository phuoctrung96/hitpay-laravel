@extends('layouts.business', [
    'title' => 'Notifications',
])

@section('business-content')
    <div class="row justify-content-center">
        <div class="col-md-12 col-lg-9 col-xl-8 main-content">
            <business-notifications></business-notifications>
        </div>
    </div>
@endsection

@push('body-stack')
    <script>
        window.Business = @json($business->toArray());
    </script>
@endpush
