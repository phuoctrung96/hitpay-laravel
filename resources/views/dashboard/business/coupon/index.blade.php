@extends('layouts.business', [
    'title' => 'Coupon'
])

@section('business-content')
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card shadow-sm mb-3">
                <div class="card-body p-4">
                    <h2 class="text-primary mb-3 title">Coupon</h2>
                    <br/>
                    <a class="btn btn-primary" href="{{ route('dashboard.business.coupon.create', $business->getKey()) }}">
                        <i class="fas fa-plus mr-2"></i> Add Coupon
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
                    @php($last = ($paginator->currentPage() - 1) * $paginator->perPage())
                    @php($count = count($paginator->items()))
                    @php($from = $count > 0 ? $last + 1 : $last)
                    <p class="small text-muted mb-0">Showing result from {{ number_format($from) }} to {{ number_format($last + count($paginator->items())) }}</p>
                </div>
                @if ($paginator->count())
                    @foreach ($paginator as $item)
                        <div class="card-body bg-light border-top p-4">
                            <div class="media">
                                <div class="media-body">
                                    @if($item->fixed_amount)
                                        <span class="font-weight-bold text-dark float-right">{{ $item->getPrice($business->currency, $item->fixed_amount) }}</span>
                                    @else
                                        <span class="font-weight-bold text-dark float-right">{{ $item->getPercent($item->percentage) }}</span>
                                    @endif
                                    <p class="font-weight-bold mb-2">{{ $item->name }}</p>
                                    @if($item->is_promo_banner)
                                        <p class="small text-secondary mb-0">ENABLE ON BANNER</p>
                                    @endif
                                </div>
                            </div>
                            <div class="media-bottom">
                                <div class="mt-2">
                                    <a href="{{route('dashboard.business.coupon.edit', [
                                    $business->getKey(),
                                    $item->getKey(),
                                ])}}">
                                        <i class="fa fa-edit"></i> <span>Edit</span>
                                    </a>
                                    <a href="{{route('dashboard.business.coupon.delete', [
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
                        <p class="small mb-0">- No coupon found -</p>
                    </div>
                </div>
                @endif
            </div>

            @if ($paginator->lastPage() > 1)
            <div class="pagination-invoice">
                <ul class="pagination">
                    <li class="page-item first{{ ($paginator->currentPage() == 1) ? ' disabled' : '' }}" >
                        <a class="page-link" href="{{ $paginator->url(1) }}">Prev</a>
                    </li>
                    @for ($i = 1; $i <= $paginator->lastPage(); $i++)
                        <li class="page-item page-number {{ ($paginator->currentPage() == $i) ? ' active' : '' }}">
                            <a class="page-link" href="{{ $paginator->url($i) }}">{{ $i }}</a>
                        </li>
                    @endfor
                    <li class="page-item last{{ ($paginator->currentPage() == $paginator->lastPage()) ? ' disabled' : '' }}">
                        <a class="page-link" href="{{ $paginator->url($paginator->currentPage()+1) }}" >Next</a>
                    </li>
                </ul>
            </div>
            @endif
        </div>
    </div>
@endsection

@push('body-stack')
@endpush
