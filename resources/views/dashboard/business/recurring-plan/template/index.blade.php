@extends('layouts.business', [
    'title' => 'Recurring Plans'
])

@section('business-content')
    <div class="row">
        <div class="col-lg-8 main-content">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <h2 class="text-primary mb-3 title">Recurring Plan Templates</h2>
                    <a class="btn btn-primary" href="{{ route('dashboard.business.recurring-plan.template.create', $business->id) }}">New Template</a>
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
                    <p class="small text-muted mb-0">Showing result from  {{  number_format($from) }} to {{ number_format($last + count($paginator->items())) }}</p>
                </div>
                @if ($paginator->count())
                    @foreach ($paginator as $item)
                        <a class="hoverable" href="{{ route('dashboard.business.recurring-plan.template.edit', [
                            $business->getKey(),
                            $item->getKey()
                        ]) }}">
                            <div class="card-body bg-light border-top p-4">
                                <div class="media">
                                    <div class="media-body">
                                        <span class="float-right">{{ $item->getPrice() }} / {{ ucfirst($item->cycle) }}</span>
                                        <p class="font-weight-bold mb-2">{{ $item->name }}</p>
                                        <p class="text-dark small mb-2"><span class="text-muted"># {{ $item->getKey() }}</span></p>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                @else
                    <div class="card-body bg-light border-top p-4">
                        <div class="text-center text-muted py-4">
                            <p><i class="fa fas fa-file fa-4x"></i></p>
                            <p class="small mb-0">- No recurring plan found -</p>
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
