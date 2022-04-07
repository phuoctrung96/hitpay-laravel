@extends('layouts.business', [
    'title' => 'Edit Product Category'
])
@section('business-content')
    <div class="row">
        <div class="col-12 col-lg-8 mb-4">
            <a href="{{ route('dashboard.business.product-categories.index', [
                $business->getKey(),
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to Categories</a>
        </div>
        <div class="col-12 col-lg-8 main-content">
            <product-category-create-edit></product-category-create-edit>
        </div>
    </div>
@endsection

@push('body-stack')
    <script>
        window.Business = @json($business);
        window.ProductCategory = @json($category)
    </script>
@endpush
