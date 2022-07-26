@php($title = __('Shopify Payment App'))

@extends('layouts.business')

@section('business-content')
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card border-0 shadow-sm mb-3">
                @if(session('success_message'))
                    <div class="alert alert-success border-left-0 border-right-0 rounded-0 alert-dismissible fade show" role="alert">
                        {{ session('success_message') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                <div class="card-body border-top px-4 py-2">
                    <p class="small text-muted mb-0">Showing the latest {{ $paginator->count() }} results</p>
                </div>
                @if ($paginator->count())
                    @foreach ($paginator as $shopifyStore)
                        <div class="card-body bg-light border-top p-4">
                            <span class="float-right">{{ $shopifyStore->shopify_name }}</span>
                            <p class="font-weight-bold mb-2"><a href="https://{{$shopifyStore->shopify_domain}}/admin">{{ $shopifyStore->shopify_domain }}</a></p>
                            <p class="text-dark small mb-2"><span class="text-muted"># {{ $shopifyStore->id }}</span></p>
                            <p class="text-dark small mb-2">
                                Created At: <span class="text-muted">{{ $shopifyStore->created_at }}</span>
                            </p>

                            <a class="small font-weight-bold text-danger" href="#" data-toggle="modal" data-target="#removeShopify">Remove</a>

                            <div id="removeShopify" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="text-danger modal-title mb-0">Remove Shopify</h5>
                                        </div>
                                        <div class="modal-body">
                                            <p class="mb-0">By removing Shopify from HitPay, checkout of this shop will removed from HitPay.</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-dismiss="modal">Close</button>
                                            <form method="post" action="{{ route('dashboard.business.shopify-payment-app.store.destroy', [$business->getKey(), $shopifyStore->getKey()]) }}">
                                                @csrf
                                                @method('delete')
                                                <button id="removeShopifyButton" type="submit" class="btn btn-danger" onclick="showSpinner()">Confirm Remove</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="card-body bg-light border-top p-4">
                        <div class="text-center text-muted py-4">
                            <p><i class="fas fa-money-check-alt fa-4x"></i></p>
                            <p class="small mb-0">- No data found -</p>
                        </div>
                    </div>
                @endif
                <div class="card-body border-top pt-2">
                </div>
            </div>
            <ul class="pagination mb-0">
                @if ($paginator->currentPage() <= 1)
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">@lang('pagination.previous')</span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->url($paginator->currentPage() - 1) }}" rel="prev">@lang('pagination.previous')</a>
                    </li>
                @endif

                @if ($paginator->currentPage() < $paginator->lastPage())
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->url($paginator->currentPage() + 1) }}" rel="next">@lang('pagination.next')</a>
                    </li>
                @else
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">@lang('pagination.next')</span>
                    </li>
                @endif
            </ul>
        </div>
    </div>
@endsection

@push('body-stack')
    <script type="text/javascript">
        function showSpinner() {
            $('#removeShopifyButton').append($('<i class="fa fa-spinner fa-spin ml-2">'));
        }
    </script>
@endpush
