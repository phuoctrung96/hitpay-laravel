@extends('layouts.business', [
    'title' => 'Cashback'
])

@section('business-content')
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card shadow-sm mb-3">
                <div class="card-body p-4">
                    <h2 class="text-primary mb-3 title">Cashback</h2>
                    <br/>
                    @if(!count($paginator->items()))
                    <a class="btn btn-primary" href="{{ route('dashboard.business.cashback.create', $business->getKey()) }}">
                        <i class="fas fa-plus mr-2"></i> Add Cashback
                    </a>
                    @endif
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
                                    <p class="font-weight-bold mb-2">{{ $item->name }}</p>
                                    <p class="text-dark small mb-0">Percent: <span class="text-muted">{{ $item->percentage }}%</span></p>
                                    <p class="text-dark small mb-0">Fixed Amount: <span class="text-muted">{{ $item->display('fixed_amount') }}</span></p>
                                    <p class="text-dark small mb-0">Minimum Order Amount: <span class="text-muted">{{ $item->display('minimum_order_amount') }}</span></p>
                                    <p class="text-dark small mb-0">Maximum Cashback: <span class="text-muted">{{ $item->display('maximum_cashback') }}</span></p>
                                    <p class="text-dark small mb-0">Channel: <span class="text-muted">{{ $item->channel }}</span></p>
                                    <p class="text-dark small mb-0">Payment Method: <span class="text-muted">{{ $item->payment_provider_charge_type }}</span></p>
                                    @if($item->ends_at)<p class="text-dark small mb-0">End Date: <span class="text-muted">{{ $item->ends_at->format('Y-m-d') }}</span></p>@endif
                                </div>
                            </div>
                            <div class="media-bottom">
                                <div class="mt-2">
                                    <a href="{{route('dashboard.business.cashback.edit', [
                                    $business->getKey(),
                                    $item->getKey(),
                                ])}}" class="">
                                        <i class="fa fa-edit"></i> <span>Edit</span>
                                    </a>
                                    <a href="{{route('dashboard.business.cashback.delete', [
                                    $business->getKey(),
                                    $item->getKey(),
                                ])}}" class="float-right btn btn-danger">
                                        <i class="fa fa-trash"></i> <span>Delete</span>
                                    </a>
                                    @if($item->enabled)
                                    <a href="{{route('dashboard.business.cashback.change-state', [
                                    $business->getKey(),
                                    $item->getKey(), 0
                                ])}}" class="float-right btn btn-dark mr-2">
                                        <span>Disable</span>
                                    </a>
                                    @else
                                        <a href="{{route('dashboard.business.cashback.change-state', [
                                    $business->getKey(),
                                    $item->getKey(), 1
                                ])}}" class="float-right btn btn-success mr-2">
                                            <span>Enable</span>
                                        </a>
                                    @endif
                                </div>

                            </div>
                        </div>
                    @endforeach
                @else
                <div class="card-body bg-light border-top p-4">
                    <div class="text-center text-muted py-4">
                        <p><i class="fa fas fa-percent fa-4x"></i></p>
                        <p class="small mb-0">- No cashback found -</p>
                    </div>
                </div>
                @endif
            </div>
            <business-help-guide :page_type="'cashback'"></business-help-guide>
        </div>
    </div>
@endsection

@push('body-stack')
@endpush
