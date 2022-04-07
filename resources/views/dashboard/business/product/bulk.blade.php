@extends('layouts.business', [
    'title' => 'Create Products In Bulk',
])

@section('business-content')
    <div class="row">
        <div class="col-12 col-lg-8 mb-4">
            <a href="{{ route('dashboard.business.product.index', [
                $business->getKey(),
                'page' => request('index_page', 1),
                'shopify_only' => request('index_shopify_only', 0),
                'status' => request('index_status', 'published'),
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to Products</a>
        </div>
        <div class="col-12 col-lg-8 main-content">
            <business-product-bulk></business-product-bulk>
        </div>
    </div>
@endsection

@push('body-stack')
    <script>
        window.Business = @json($business);
    </script>
@endpush
