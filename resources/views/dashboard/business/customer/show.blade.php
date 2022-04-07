@php($customer_name = $customer->name ?? $customer->email)
@extends('layouts.business', [
    'title' => 'Customers - '.$customer_name,
])

@section('business-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 mb-4">
            <a href="{{ route('dashboard.business.customer.index', [
                $business->getKey(),
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to Customers</a>
        </div>
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card shadow-sm mb-3">
                <div class="card-body p-4">
                    <label class="small text-uppercase text-muted mb-3">Customer # {{ $customer->getKey() }}</label>
                    <h2 class="text-primary mb-3 title">{{ $customer_name }}</h2>
                    @if ($customer->name)
                        <p class="text-dark small mb-0">Email Address: <span class="text-muted">{{ $customer->email }}</span></p>
                    @endif
                    <p class="text-dark small mb-0">Phone Number: <span class="text-muted">{{ $customer->phone_number }}</span></p>
                    <p class="text-dark small mb-3">Address: <span class="text-muted">{{ implode(', ', array_filter([
                        $customer->street,
                        $customer->city,
                        $customer->state,
                        strtoupper($customer->country),
                    ])) }}</span></p>
                    @if(session('success_message'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success_message') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
                    <a href="{{ route('dashboard.business.customer.edit', [
                        $business->getKey(),
                        $customer->getKey(),
                    ]) }}">
                        <i class="fas fa-edit mr-2"></i> Edit Customer
                    </a>
                </div>
                <div class="card-body border-top px-4 py-2">
                    @php($last = ($paginator->currentPage() - 1) * $paginator->perPage())
                    @php($count = count($paginator->items()))
                    @php($from = $count > 0 ? $last + 1 : $last)
                    <p class="small text-muted mb-0">Showing result from {{ number_format($from) }} to {{ number_format($last + count($paginator->items())) }}</p>
                </div>
                @if ($paginator->count())
                    @foreach ($paginator as $item)
                        <a class="hoverable" href="{{ route('dashboard.business.charge.show', [
                            $business->getKey(),
                            $item->getKey()
                        ]) }}">
                            <div class="card-body bg-light border-top p-4">
                                <div class="media">
                                    <div class="media-body">
                                        <span class="float-right">{{ $item->display('amount') }}</span>

                                        <p>{{ $item->remark ?? 'Payment' }}</p>
                                        <p class="text-dark small mb-2"><span class="text-muted"># {{ $item->getKey() }}</span></p>
                                        <p class="text-dark small mb-0">Order ID #: <span class="text-muted">{{ $item->plugin_provider_reference ?? 'None' }}</span></p>
                                        <p class="text-dark small mb-0">Payment Method: <span class="text-muted">
                                        {!! \App\Enumerations\Business\PaymentMethodType::displayName($item->payment_provider_charge_method) !!}
                                        </span></p>
                                        <p class="text-dark small mb-0">Relatable: <span class="text-muted">
                                                @if ($item->target)
                                                    @switch (get_class($item->target))
                                                        @case (\App\Business\Order::class)
                                                            Order
                                                            @break
                                                        @case (\App\Business\RecurringBilling::class)
                                                            Recurring Plan
                                                            @break
                                                    @endswitch
                                                @else
                                                    None
                                                @endif
                                        </span></p>
                                        @switch ($item->status)
                                            @case ('succeeded')
                                                <p class="text-dark small mb-0">Collected at {{ $item->closed_at->format('h:ia \o\n F d, Y (l)') }}</p>
                                                @break
                                            @case ('refunded')
                                                <span class="badge badge-warning">Refunded</span>
                                                @break
                                            @case ('requires_payment_method')
                                                <span class="badge badge-info">Payment In Progress</span>
                                                @break
                                            @case ('requires_customer_action')
                                                <span class="badge badge-info">Waiting For Customer</span>
                                                @break
                                            @case ('failed')
                                                <span class="badge badge-danger">Failed</span>
                                                @break
                                            @case ('canceled')
                                            @case ('expired')
                                                <span class="badge badge-secondary">Expired</span>
                                                @break
                                            @default
                                                <span class="badge badge-secondary">{{ $item->status }}</span>
                                        @endswitch
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                @else
                    <div class="card-body bg-light border-top p-4">
                        <div class="text-center text-muted py-4">
                            <p><i class="fa fas fa-dollar-sign fa-4x"></i></p>
                            <p class="small mb-0">- No charges found for this customer -</p>
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

@push('body-stack')
    <script>
        function check(trigger) {
            let current = new URL(window.location.href);
            let query = current.search;
            let params = new URLSearchParams(query);

            if ($(trigger).is(':checked')) {
                params.set('order_related_only', 1);
            } else {
                params.set('order_related_only', 0);
            }

            current.search = params.toString();
            window.location = current.toString();
        }
    </script>
@endpush
