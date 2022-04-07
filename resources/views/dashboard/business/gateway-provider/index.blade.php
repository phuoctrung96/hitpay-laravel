@extends('layouts.business', [
    'title' => 'Integrations'
])

@section('business-content')
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <h2 class="text-primary mb-0 title float-right"><a href="{{ route('dashboard.business.gateway.create', [
                            $business->getKey()
                        ]) }}" class="btn btn-primary"><i class="fa fa-plus"></i> Add New</a></h2>
                    <h2 class="text-primary mb-0 title">Integrations</h2>                    
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
                        <div class="card-body bg-light border-top p-4">
                            <div class="media">
                                <div class="media-body">
                                    <p class="float-right">
                                        <a href="{{ route('dashboard.business.gateway.edit', [
                                            $business->getKey(),
                                            $item->getKey(),
                                        ]) }}">
                                            <i class="fas fa-edit mr-2"></i>
                                        </a>
                                        <a href="{{ route('dashboard.business.gateway.delete', [
                                            $business->getKey(),
                                            $item->getKey(),
                                        ]) }}" class="text-danger">
                                            <i class="fas fa-trash mr-2"></i>
                                        </a>
                                    </p>
                                    @if (isset($names[$item->name]))
                                        <p class="font-weight-bold mb-2">{{ strtoupper($names[$item->name]) }}</p>
                                        <p class="font-weight-bold mb-2">Payment Methods</p>
                                    @endif
                                    @foreach ($item->array_methods as $method)
                                        <p class="text-dark small mb-2"><span class="text-muted">{{ $method }}</span></p>
                                    @endforeach
                                </div>
                            </div>
                        </div>    
                    @endforeach
                @else
                    <div class="card-body bg-light border-top p-4">
                        <div class="text-center text-muted py-4">
                            <p><i class="fa fas fa-user-friends fa-4x"></i></p>
                            <p class="small mb-0">- No gateway provider found -</p>
                        </div>
                    </div>
                @endif
                <div class="card-body border-top px-4 py-2">
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
            <business-help-guide :page_type="'integrations'"></business-help-guide>
        </div>
    </div>
@endsection
