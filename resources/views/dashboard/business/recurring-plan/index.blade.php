@extends('layouts.business', [
    'title' => 'Recurring Plans'
])

@section('business-content')
    <div class="row">
        <div class="col-lg-12 main-content">
            <div class="btn-group btn-group-sm mb-3 w-100 shadow-sm">
                <a class="btn col {{ $status === 'active' ? 'active btn-outline-primary' : 'bg-light' }}" href="{{ route('dashboard.business.recurring-plan.index', [
                    $business->getKey(),
                    'status' => 'active',
                ]) }}">Active</a>
                <a class="btn col {{ $status === 'scheduled' ? 'active btn-outline-primary' : 'btn-light' }}" href="{{ route('dashboard.business.recurring-plan.index', [
                    $business->getKey(),
                    'status' => 'scheduled',
                ]) }}">Scheduled</a>
                <a class="btn col {{ $status === 'completed' ? 'active btn-outline-primary' : 'btn-light' }}" href="{{ route('dashboard.business.recurring-plan.index', [
                    $business->getKey(),
                    'status' => 'completed',
                ]) }}">Completed</a>
                <a class="btn col {{ $status === 'canceled' ? 'active btn-outline-primary' : 'btn-light' }}" href="{{ route('dashboard.business.recurring-plan.index', [
                    $business->getKey(),
                    'status' => 'canceled',
                ]) }}">Canceled</a>
            </div>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <h2 class="text-primary mb-3 title">Recurring Plans</h2>
                    <a class="btn btn-primary" href="{{ route('dashboard.business.recurring-plan.create', $business->id) }}">Create New</a>
                    <a class="btn btn-text" href="{{ route('dashboard.business.recurring-plan.create-template', $business->id) }}">New with Template</a>
                </div>
                <div class="card-body border-top px-4 py-2">
                    @php($last = ($paginator->currentPage() - 1) * $paginator->perPage())
                    @php($count = count($paginator->items()))
                    @php($from = $count > 0 ? $last + 1 : $last)
                    <p class="small text-muted mb-0">Showing result from  {{  number_format($from) }} to {{ number_format($last + count($paginator->items())) }}</p>
                </div>
                @if ($paginator->count())
                    @foreach ($paginator as $item)
                        <a class="hoverable" href="{{ route('dashboard.business.recurring-plan.show', [
                            $business->getKey(),
                            $item->getKey()
                        ]) }}">
                            <div class="card-body bg-light border-top p-4">
                                <div class="media">
                                    <div class="media-body">
                                        <span class="float-right">{{ $item->getPrice() }}</span>
                                        <p class="font-weight-bold mb-2">{{ $item->name }}</p>
                                        <p class="text-dark small mb-2"><span class="text-muted"># {{ $item->getKey() }}</span></p>
                                        <p class="text-dark mb-2">{{ $item->customer_name }}{{ $item->customer ? ' ('.$item->customer->name.')' : '' }}</p>
                                        @if ($item->times_to_be_charged)
                                            <p class="text-dark small">Charged Times: <span class="text-success font-weight-bold">{{ $item->times_charged }} / {{ $item->times_to_be_charged }}</span></p>
                                        @endif
                                        <p class="text-dark small mb-0">Created at {{ $item->created_at->format('h:ia \o\n F d, Y (l)') }}</p>
                                        @switch ($item->status)
                                            @case ('active')
                                                <p class="text-dark small mb-0">Next renewal at {{ $item->expires_at->format('F d, Y (l)') }}</p>
                                                @break
                                            @case ('completed')
                                                <p class="text-success font-weight-bold small mb-0">Completed</p>
                                                @break
                                            @case ('scheduled')
                                                @if ($item->expires_at->isPast())
                                                    <p class="text-dark small mb-0"><span class="badge badge badge-danger">Canceled</span> at {{ $item->expires_at->format('h:ia \o\n F d, Y (l)') }}</p>
                                                @else
                                                    <p class="text-dark small mb-0">Automatic canceled at {{ $item->expires_at->format('h:ia \o\n F d, Y (l)') }}</p>
                                                @endif
                                                @break
                                            @case ('canceled')
                                                <p class="text-dark small mb-0"><span class="badge badge badge-danger">Canceled</span> at {{ $item->expires_at->format('h:ia \o\n F d, Y (l)') }}</p>
                                                @break
                                            @default
                                                <span class="badge badge-secondary">{{ $item->status }}</span>
                                        @endswitch
                                        <a href="{{route('dashboard.business.recurring-plan.edit', [$business->getKey(),
                                                 $item->getKey()])}}" class="float-right">Edit</a>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                @else
                    <div class="card-body bg-light border-top p-4">
                        <div class="text-center text-muted py-4">
                            <p><i class="fa fas fa-sync fa-4x"></i></p>
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
    <business-help-guide :page_type="'recurring_plans'"></business-help-guide>
@endsection
@push('body-stack')
    <script>
        window.Business = @json($business->toArray());
    </script>
@endpush
