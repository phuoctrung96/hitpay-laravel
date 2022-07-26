@extends('layouts.business', [
    'title' => 'Create Discount'
])
@section('business-content')
    <div class="row">
        <div class="col-12 col-lg-8 mb-4">
            <a href="{{ route('dashboard.business.discount.home', [
                $business->getKey(),
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to Discounts</a>
        </div>
        <div class="col-12 col-lg-8 main-content">
            <business-discount-create></business-discount-create>
        </div>
    </div>
@endsection

