@extends('layouts.business', [
    'title' => 'Coupon'
])

@section('business-content')
    <div class="row justify-content-center">
        <business-coupon-list></business-coupon-list>
    </div>
@endsection

@push('body-stack')
    <script>
        window.Business = @json($business);
    </script>
@endpush
