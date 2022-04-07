@extends('layouts.admin', [
    'title' => 'Terminals'
])

@section('admin-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <h2 class="text-primary mb-0 title">Registered Terminals</h2>
                </div>
                <div class="card-body border-top px-4 py-2">
                    <p class="small text-muted mb-0">Showing the latest {{ $paginator->count() }} results</p>
                </div>
                @if ($paginator->count())
                    @foreach ($paginator as $item)
                        <div class="card-body bg-light border-top p-4">
                            <p class="font-weight-bold mb-2">{{ $item->name }}</p>
                            <p class="small font-weight-bold mb-2">{{ $item->remark }}</p>
                            <p class="text-dark small mb-2"><span class="text-muted"># {{ $item->getKey() }}</span></p>
                            <p class="text-dark small mb-0">Stripe Reader ID : <span class="text-muted">{{ $item->stripe_terminal_id }}</span></p>
                            <p class="text-dark small mb-0">Device Type : <span class="text-muted">{{ $item->device_type }}</span></p>
                            <p class="small mb-2">Business : <span class="text-muted">{{ $item->location->business->name }}</span></p>
                        </div>
                    @endforeach
                @else
                    <div class="card-body bg-light border-top p-4">
                        <div class="text-center text-muted py-4">
                            <p><i class="fas fa-calculator fa-4x"></i></p>
                            <p class="small mb-0">- No terminal found -</p>
                        </div>
                    </div>
                @endif
                <div class="card-body border-top pt-0">
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
