@extends('layouts.business', [
    'title' => 'Create Product',
])

@section('business-content')
    <div class="row">
        <div class="col-12 col-lg-8 mb-4">
            <a href="#" data-toggle="modal" data-target="#CloseProduct"><i class="fas fa-reply fa-fw mr-3"></i> Back to Products</a>
        </div>
        <div class="col-12 col-lg-8 main-content">
            <business-product></business-product>
        </div>
    </div>
    <div id="CloseProduct" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <p class="text-danger font-weight-bold mb-0">Are you sure? Your entered data will be lost.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Close</button>
                    <a href="{{ route('dashboard.business.product.index', [
                            $business->getKey(),
                            'page' => request('index_page', 1),
                            'shopify_only' => request('index_shopify_only', 0),
                            'status' => request('index_status', 'published'),
                        ]) }}"  class="btn btn-danger">Confirm</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('body-stack')
    <script>
        window.Business = @json($business);
        window.Categories = @json($categories);
    </script>
@endpush
