@extends('layouts.admin', [
    'title' => 'Bank Accounts - '.$business->getName(),
])

@section('admin-content')
    <div class="row">
        <div class="col-12 col-md-9 col-lg-8 mb-4">
            <a href="{{ route('admin.business.show', $business->getKey()) }}">
                <i class="fas fa-reply fa-fw mr-3"></i> Back to {{ $business->getName() }}</a>
        </div>
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <p class="text-uppercase text-muted mb-0">{{ $business->getName() }}</p>
                    <h2 class="text-primary mb-3 title">Bank Accounts</h2>
                </div>
                @if ($paginator->count())
                    @foreach ($paginator as $item)
                        <div class="card-body bg-light border-top p-4">
                            <p>Holder Name: <span class="font-weight-bold mb-2">{{ $item->holder_name }}</span></p>
                            <p>Number: <span class="font-weight-bold mb-2">{{ $item->number }}</span></p>
                            <p>Holder Type: <span class="font-weight-bold mb-2">{{ $item->holder_type }}</span></p>
                            @if ($item->remark)
                                <p class="text-dark mb-2">Remark: {{ $item->remark }}</p>
                            @endif
                            <p>
                                Created At: <span class="font-weight-bold mb-2">{{ $item->created_at->format('d M Y H:i:s') }}</span>
                            </p>
                            <p>
                                @if($item->hitpay_default)<span class="badge badge-primary">HITPAY</span>@endif
                                @if($item->stripe_external_account_default) <span class="badge badge-success">STRIPE</span>@endif
                            </p>
                        </div>
                    @endforeach
                @else
                    <div class="card-body bg-light border-top p-4">
                        <div class="text-center text-muted py-4">
                            <p><i class="fa fas fa-dollar-sign fa-4x"></i></p>
                            <p class="small mb-0">- No bank account found -</p>
                        </div>
                    </div>
                @endif
                <div class="card-body border-top py-2">
                    <p class="small text-muted mb-0">Total of {{ number_format($paginator->total()) }} records.</p>
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
