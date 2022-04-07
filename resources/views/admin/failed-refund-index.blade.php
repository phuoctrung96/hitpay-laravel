@extends('layouts.admin', [
    'title' => 'Failed Refunds'
])

@section('admin-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 main-content">
            <div class="form-group">
                <form class="input-group input-group-lg" action="{{ route('admin.failed-refund.index') }}">
                    <input type="text" class="form-control border-0 shadow-sm" placeholder="Search By Charge ID" title="Search By Charge ID" name="charge_id" value="{{ request('charge_id') }}">
                    <div class="input-group-append">
                        <button class="btn btn-primary shadow-sm"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <h2 class="text-primary mb-0 title">Failed Refunds</h2>
                </div>
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
                                    <span class="float-right">{{ $item->display('amount') }}</span>
                                    <p class="text-dark small font-weight-bold mb-2">Refund # {{ $item->getKey() }}</p>
                                    <p class="text-dark small font-weight-bold mb-2">Failed Reason:</p>
                                    <p class="font-weight-bold text-danger mb-2">{{ $item->remark }}</p>
                                    <hr class="my-2">
                                    <p class="text-dark small mb-2"><a href="{{ route('admin.business.charge.index', [
                                        'business_id' => $item->charge->business_id,
                                        'keyword' => $item->business_charge_id,
                                    ]) }}">Charge # {{ $item->getKey() }}</a></p>
                                    <p class="small mb-2">Charge Amount: <span class="font-weight-bold text-success">{{ $item->display('amount') }}</span></p>
                                    <p class="small mb-0">Name: <span class="text-muted">{{ $item->charge->customer_name ?? '-' }}</span></p>
                                    <p class="small mb-0">Email: <span class="text-muted">{{ $item->charge->customer_email ?? '-' }}</span></p>
                                    <p class="small mb-2">Phone Number: <span class="text-muted">{{ $item->charge->customer_phone_number ?? '-' }}</span></p>
                                    <p class="small mb-0">Channel: <span class="text-muted">{{ ucwords(str_replace('_', ' ', $item->charge->channel)) }}</span></p>
                                    <p class="small mb-0">Business: <a href="{{ route('admin.business.show', [
                                        'business_id' => $item->charge->business_id,
                                    ]) }}">{{ $item->charge->business->name }}</a></p>
                                    <p class="small mb-0">Platform Reference ID: <span class="text-muted">{{ ucwords(str_replace('_', ' ', $item->charge->payment_provider)) }} <span class="font-weight-bold">{{ $item->payment_provider_charge_id }}</span></span></p>
                                    @if ($item->charge->channel === \App\Enumerations\Business\Channel::PAYMENT_GATEWAY)
                                        <p class="small mb-0">Plugin: <span class="text-muted">{{ ucwords(str_replace('_', ' ', $item->charge->plugin_provider)) }}</span></p>
                                        <p class="small mb-0">Reference: <span class="text-muted">{{ $item->charge->plugin_provider_reference }}</span></p>
                                        @if ($item->charge->plugin_provider_order_id)
                                            <p class="small mb-0">Order ID: <span class="text-muted">{{ $item->charge->plugin_provider_order_id }}</span></p>
                                        @endif
                                        <p class="small mb-0">Callback Status: <span class="text-muted">{{ $item->charge->is_successful_plugin_callback ? 'Succeeded' : 'Failed' }}</span></p>
                                    @endif
                                    <p class="text-dark small mb-0">Payment Method: <span class="text-muted">
                                    {!! \App\Enumerations\Business\PaymentMethodType::displayName($item->charge->payment_provider_charge_method) !!}
                                    </span></p>
                                    @if ($item->charge->payment_provider_charge_method === 'card' && isset($item->data['source']['card']['country']))
                                        @php($country = strtolower($item->charge->data['source']['card']['country']))
                                        <p class="text-dark small mb-0">Card Country: <span class="text-muted">@lang('misc.country.'.$country)</span></p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="card-body bg-light border-top p-4">
                        <div class="text-center text-muted py-4">
                            <p><i class="fa fas fa-dollar-sign fa-4x"></i></p>
                            <p class="small mb-0">- No failed refund found -</p>
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
