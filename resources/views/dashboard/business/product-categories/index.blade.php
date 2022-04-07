@extends('layouts.business', [
    'title' => 'Product Categories'
])

@section('business-content')
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card shadow-sm mb-3">
                <div class="card-body p-4">
                    <h2 class="text-primary mb-3 title">Product Categories</h2>
                    <br/>
                    <a class="btn btn-primary" href="{{ route('dashboard.business.product-categories.create', $business->getKey()) }}">
                        <i class="fas fa-plus mr-2"></i> Add Category
                    </a>
                </div>
                @if(session('success_message'))
                    <div class="alert alert-success border-left-0 border-right-0 rounded-0 alert-dismissible fade show" role="alert">
                        {{ session('success_message') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
                <div class="card-body border-top px-4 py-2">
                    @php($last = ($productsCategories->currentPage() - 1) * $productsCategories->perPage())
                    @php($count = count($productsCategories->items()))
                    @php($from = $count > 0 ? $last + 1 : $last)
                    <p class="small text-muted mb-0">Showing result from {{ number_format($from) }} to {{ number_format($last + count($productsCategories->items())) }}</p>
                </div>
                @if ($productsCategories->count())
                    @foreach ($productsCategories as $item)
                        <div class="card-body bg-light border-top p-4">
                            <div class="media">
                                <div class="media-body">
                                    <p class="font-weight-bold mb-2">{{ $item->name }}</p>
                                    @if ($item->description)
                                        <p class="text-dark small mb-2">{{$item->description}}
                                    @endif
                                    <p>@if($item->active) Active @else Not Active @endif</p>
                                </div>
                            </div>
                            <div class="media-bottom">
                                <div class="mt-2">
                                    <a href="{{route('dashboard.business.product-categories.edit', [
                                    $business->getKey(),
                                    $item->getKey(),
                                ])}}">
                                        <i class="fa fa-edit"></i> <span>Edit</span>
                                    </a>
                                    <a href="{{route('dashboard.business.product-categories.delete', [
                                    $business->getKey(),
                                    $item->getKey(),
                                ])}}" class="float-right">
                                        <i class="fa fa-trash"></i> <span>Delete</span>
                                    </a>
                                </div>

                            </div>
                        </div>
                    @endforeach
                @else
                <div class="card-body bg-light border-top p-4">
                    <div class="text-center text-muted py-4">
                        <p><i class="fa fas fa-percent fa-4x"></i></p>
                        <p class="small mb-0">- No Product Category found -</p>
                    </div>
                </div>
                @endif
            </div>

            @if ($productsCategories->lastPage() > 1)
            <div class="pagination-invoice">
                <ul class="pagination">
                    <li class="page-item first{{ ($productsCategories->currentPage() == 1) ? ' disabled' : '' }}" >
                        <a class="page-link" href="{{ $productsCategories->url(1) }}">Prev</a>
                    </li>
                    @for ($i = 1; $i <= $productsCategories->lastPage(); $i++)
                        <li class="page-item page-number {{ ($productsCategories->currentPage() == $i) ? ' active' : '' }}">
                            <a class="page-link" href="{{ $productsCategories->url($i) }}">{{ $i }}</a>
                        </li>
                    @endfor
                    <li class="page-item last{{ ($productsCategories->currentPage() == $productsCategories->lastPage()) ? ' disabled' : '' }}">
                        <a class="page-link" href="{{ $productsCategories->url($productsCategories->currentPage()+1) }}" >Next</a>
                    </li>
                </ul>
            </div>
            @endif
        </div>
    </div>
@endsection

@push('body-stack')
@endpush
