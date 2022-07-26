@extends('layouts.business', [
    'title' => 'Edit Coupon'
])
@section('business-content')
    <div class="row">
        <div class="col-12 col-lg-8 mb-4">
            <a href="{{ route('dashboard.business.coupon.home', [
                $business->getKey(),
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to Coupons</a>
        </div>
        <div class="col-12 col-lg-8 main-content">
            <business-coupon-edit></business-coupon-edit>
        </div>
    </div>
@endsection

@push('body-stack')
    <script>
        window.Coupon = @json($coupon)
    </script>
@endpush
