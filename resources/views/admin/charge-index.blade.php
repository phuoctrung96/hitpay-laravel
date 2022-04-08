@extends('layouts.admin', [
    'title' => 'Charges'
])

@section('admin-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 main-content">
            <div class="btn-group btn-group-sm mb-3 w-100 shadow-sm">
                <a class="btn col {{ $status === 'succeeded' ? 'active btn-outline-primary' : 'bg-light' }}" href="{{ route('admin.charge.index', [
                    'status' => 'succeeded',
                ]) }}">Succeeded</a>
                <a class="btn col {{ $status === 'refunded' ? 'active btn-outline-primary' : 'btn-light' }}" href="{{ route('admin.charge.index', [
                    'status' => 'refunded',
                ]) }}">Refunded</a>
            </div>
            <div class="form-group">
                <form class="input-group input-group-lg" action="{{ route('admin.charge.index') }}">
                    <input class="form-control border-0 shadow-sm" placeholder="Search By ID Or Order ID Or Gateway Provider Reference Or Email" title="Search Charge" name="keyword" value="{{ request('keyword') }}">
                    <div class="input-group-append">
                        <button class="btn btn-primary shadow-sm"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>
            <div class="form-group">
                <form class="input-group input-group-lg" action="{{ route('admin.charge.index', [
                    'status' => $status,
                ]) }}">
                    <div class="input-group-prepend">
                        <select class="custom-select-lg border-0 rounded-left shadow-sm" title="Currency" name="currency">
                            @php($currentCurrency = request('currency'))
                            @foreach (\App\Enumerations\Business\SupportedCurrencyCode::listConstants() as $currency)
                                @if ($currentCurrency)
                                    @if ($currency === request('currency'))
                                        <option value="{{ $currency }}" selected>{{ strtoupper($currency) }}</option>
                                    @else
                                        <option value="{{ $currency }}">{{ strtoupper($currency) }}</option>
                                    @endif
                                @elseif ($currency === \App\Enumerations\Business\SupportedCurrencyCode::SGD)
                                    <option value="{{ $currency }}" selected>{{ strtoupper($currency) }}</option>
                                @else
                                    <option value="{{ $currency }}">{{ strtoupper($currency) }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <input type="number" class="form-control border-0 shadow-sm" placeholder="Search By Amount ({{ ucfirst($status) }})" title="Search Charge" name="amount" step=".01" value="{{ request('amount') }}">
                    <div class="input-group-append">
                        <button class="btn btn-primary shadow-sm"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <h2 class="text-primary mb-3 title">All Charges</h2>
                    <label class="text-uppercase text-muted mb-0">{{ $status === 'succeeded' ? 'Confirmed' : ucfirst($status) }} Charges</label>
                </div>
                <div class="card-body border-top px-4 py-2">
                    @php($last = ($paginator->currentPage() - 1) * $paginator->perPage())
                    @php($count = count($paginator->items()))
                    @php($from = $count > 0 ? $last + 1 : $last)
                    <p class="small text-muted mb-0">Showing result from  {{  number_format($from) }} to {{ number_format($last + count($paginator->items())) }}</p>
                </div>
                @if ($paginator->count())
                    @foreach ($paginator as $item)
                        <a href="{{ route('admin.charge.show', $item->getKey()) }}">
                        <div class="card-body bg-light border-top p-4">
                            <div class="media">
                                <div class="media-body">
                                    <span class="float-right">{{ $item->display('amount') }}</span>
                                    <p class="font-weight-bold mb-2">{{ $item->display('customer_name') }}</p>
                                    <p class="text-dark small mb-2"><span class="text-muted"># {{ $item->getKey() }}</span></p>
                                    @if ($item->remark)
                                        <p class="text-dark mb-2">{{ $item->remark }}</p>
                                    @endif
                                    <p class="small mb-0">Name: <span class="text-muted">{{ $item->customer_name ?? '-' }}</span></p>
                                    <p class="small mb-0">Email: <span class="text-muted">{{ $item->customer_email ?? '-' }}</span></p>
                                    <p class="small mb-2">Phone Number: <span class="text-muted">{{ $item->customer_phone_number ?? '-' }}</span></p>
                                    <p class="small mb-0">Channel: <span class="text-muted">{{ ucwords(str_replace('_', ' ', $item->channel)) }}</span></p>
                                    <p class="small mb-0">Business: <a href="{{ route('admin.business.show', [
                                        'business_id' => $item->business_id,
                                    ]) }}">{{ $item->business->name }}</a></p>
                                    <p class="small mb-0">Platform Reference ID: <span class="text-muted">{{ ucwords(str_replace('_', ' ', $item->payment_provider)) }} <span class="font-weight-bold">{{ $item->payment_provider_charge_id }}</span></span></p>
                                    @if ($item->channel === \App\Enumerations\Business\Channel::PAYMENT_GATEWAY)
                                        <p class="small mb-0">Plugin: <span class="text-muted">{{ ucwords(str_replace('_', ' ', $item->plugin_provider)) }}</span></p>
                                        <p class="small mb-0">Reference: <span class="text-muted">{{ $item->plugin_provider_reference }}</span></p>
                                        @if ($item->plugin_provider_order_id)
                                            <p class="small mb-0">Order ID: <span class="text-muted">{{ $item->plugin_provider_order_id }}</span></p>
                                        @endif
                                        <p class="small mb-0">Callback Status: <span class="text-muted">{{ $item->is_successful_plugin_callback ? 'Succeeded' : 'Failed' }}</span></p>
                                    @endif
                                    <p class="text-dark small mb-0">Payment Method: <span class="text-muted">
                                    {!! \App\Enumerations\Business\PaymentMethodType::displayName($item->payment_provider_charge_method) !!}
                                    </span></p>
                                    @if ($item->target && $item->target instanceof \App\Business\RecurringBilling && $item->target->payment_provider === \App\Enumerations\PaymentProvider::DBS_SINGAPORE)
                                        <p class="text-dark small mb-0">Bill Reference:: <span class="text-muted">{{ $item->target->dbs_dda_reference }}</span></p>
                                    @endif
                                    @if ($item->payment_provider_charge_method === 'card' && isset($item->data['source']['card']['country']))
                                        @php($country = strtolower($item->data['source']['card']['country']))
                                        <p class="text-dark small mb-0">Card Country: <span class="text-muted">@lang('misc.country.'.$country)</span></p>
                                    @endif
                                    @switch ($item->status)
                                        @case ('succeeded')
                                            <p class="text-dark small mb-0">All Inclusive Fee: <span class="text-muted">{{ $item->display('all_inclusive_fee') }}{{ ($originalFee = $item->display('all_inclusive_fee_original_currency')) ? ' ('.$originalFee.')' : '' }}</span></p>
                                            <p class="text-dark small mb-0">Collected at {{ $item->closed_at->format('h:ia \o\n F d, Y (l)') }}</p>
                                            @break
                                        @case ('refunded')
                                            <span class="badge badge-warning">Refunded</span>
                                            <p class="text-dark small mb-0">Created at {{ $item->created_at->format('h:ia \o\n F d, Y (l)') }}</p>
                                            <p class="text-dark small mb-0">Fully refunded at {{ $item->closed_at->format('h:ia \o\n F d, Y (l)') }}</p>
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
                                    @if ($item->target)
                                        @switch (get_class($item->target))
                                            @case (\App\Business\Order::class)
                                            <span class="badge badge-primary">Order</span>
                                            <br><span class="small text-muted"># {{ $item->target->id }}</span>
                                            @break
                                            @case (\App\Business\RecurringBilling::class)
                                            <span class="badge badge-info">Recurring Plan</span>
                                            <br><span class="small">ID : <span class="text-muted"># {{ $item->target->id }}</span></span>
                                            @if ($item->target->payment_provider === \App\Enumerations\PaymentProvider::DBS_SINGAPORE)
                                                <br><span class="small">Direct Debit Reference : <span class="text-muted">{{ $item->target->dbs_dda_reference ?? '-' }}</span></span>
                                            @elseif ($item->target->payment_provider === \App\Enumerations\PaymentProvider::STRIPE_SINGAPORE)
                                                <br><span class="small">Stripe Payment Method ID : <span class="text-muted">{{ $item->target->payment_provider_payment_method_id ?? '-' }}</span></span>
                                            @endif
                                            @break
                                        @endswitch
                                    @endif

                                    @if ($item->refunds->count())
                                        <p class="small mb-0">Refunded Records:</p>
                                        <ol class="small mb-0">
                                            @foreach ($item->refunds as $refund)
                                                <li><span class="font-weight-bold">{{ getFormattedAmount($item->currency, $refund->amount) }}</span> <span class="text-muted">refunded at {{ $refund->created_at->toDateTimeString() }}.</span><br><small class="text-monospace">Reference ID: {{ $refund->id }}</small></li>
                                            @endforeach
                                        </ol>
                                    @endif
                                </div>
                            </div>
                        </div>
                        </a>
                    @endforeach
                @else
                    <div class="card-body bg-light border-top p-4">
                        <div class="text-center text-muted py-4">
                            <p><i class="fa fas fa-dollar-sign fa-4x"></i></p>
                            <p class="small mb-0">- No charge found -</p>
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
        <div class="col-md-3 col-lg-4">
            <admin-business-charge-export class="mb-3"></admin-business-charge-export>
            <admin-business-refund-export :type="'refunds'" class="mb-3"></admin-business-refund-export>
            <admin-business-refund-export :type="'autorefunds'" class="mb-3"></admin-business-refund-export>
            <admin-business-refund-export :type="'cashbacks'"></admin-business-refund-export>
            <admin-business-referral-fees-export></admin-business-referral-fees-export>
        </div>
    </div>
@endsection

@push('body-stack')
    <script>
        window.Business = null;
    </script>
@endpush
