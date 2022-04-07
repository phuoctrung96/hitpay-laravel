@extends('layouts.business', [
    'title' => 'Charges'
])

@section('business-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 main-content">
            <div class="btn-group btn-group-sm mb-3 w-100 shadow-sm">
                <a class="btn col {{ $status === 'succeeded' ? 'active btn-outline-primary' : 'bg-light' }}" href="{{ route('dashboard.business.charge.index', [
                    $business->getKey(),
                    'status' => 'succeeded',
                    'order_related_only' => $order_related_only,
                ]) }}">Succeeded</a>
                <a class="btn col {{ $status === 'refunded' ? 'active btn-outline-primary' : 'btn-light' }}" href="{{ route('dashboard.business.charge.index', [
                    $business->getKey(),
                    'status' => 'refunded',
                    'order_related_only' => $order_related_only,
                ]) }}">Refunded</a>
            </div>
            <div class="form-group">
                <form class="input-group input-group-lg" action="{{ route('dashboard.business.charge.index', [
                    'business_id' => $business->getKey(),
                ]) }}">
                    <input class="form-control border-0 shadow-sm" placeholder="Search By Customer Name / Amount / Remarks / Order ID / Date of Transaction" title="Search Charge" name="keywords" value="{{ $keywords = request('keywords') }}">
                    <div class="input-group-append">
                        <button class="btn btn-primary shadow-sm"><i class="fas fa-search"></i></button>
                    </div>
                </form>
                <span class="small text-muted">Separate keywords by space, maximum 3 keywords will be processed.</span>
            </div>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    @if (strlen($keywords))
                        <h2 class="text-primary mb-3 title">Charge Results for "{{ $keywords }}"</h2>
                    @else
                        <h2 class="text-primary mb-3 title">{{ $status === 'succeeded' ? 'Confirmed' : ucfirst($status) }} Charges</h2>
                    @endif
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="order-related-check" onchange="check(this)"{{ $order_related_only ? ' checked' : '' }}>
                        <label class="custom-control-label" for="order-related-check">Show order related charges only</label>
                    </div>
                </div>
                <div class="card-body border-top px-4 py-2">
                    @php($last = ($paginator->currentPage() - 1) * $paginator->perPage())
                    @php($count = count($paginator->items()))
                    @php($from = $count > 0 ? $last + 1 : $last)
                    <p class="small text-muted mb-0">Showing result from  {{  number_format($from) }} to {{ number_format($last + count($paginator->items())) }}</p>
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
                                        {{--                                        don t use float--}}
                                        <span class="float-right">{{ $item->display('amount') }}</span>
                                        <p class="font-weight-bold mb-2">{{ $item->display('customer_name') }}</p>
                                        <p class="text-dark small mb-2"><span class="text-muted"># {{ $item->getKey() }}</span></p>
                                        @if ($item->display('shop_name'))
                                            <p class="text-dark small mb-0">Shop Name: <span class="text-muted">{{ $item->display('shop_name') ?? '-' }}</span></p>
                                        @endif
                                        @if ($item->plugin_provider_order_id)
                                            <p class="small mb-0">Order ID: <span class="text-muted">{{ $item->plugin_provider_order_id }} (Ref: {{ $item->plugin_provider_reference }})</span></p>
                                        @else
                                            <p class="text-dark small mb-2">Order ID: <span class="text-muted">{{ $item->plugin_provider_reference }}</span></p>
                                        @endif
                                        @if ($item->remark)
                                            <p class="text-dark mb-2">{{ $item->remark }}</p>
                                        @endif
                                        @if (!empty($item->customer_email))
                                            <p class="text-dark small mb-0">Email: <span class="text-muted">{{ $item->customer_email }}</span></p>
                                        @endif
                                        <p class="text-dark small mb-0">Payment Method: <span class="text-muted">
                                        {!! \App\Enumerations\Business\PaymentMethodType::displayName($item->payment_provider_charge_method) !!}
                                        </span></p>
                                        @if ($item->channel === \App\Enumerations\Business\Channel::PAYMENT_GATEWAY)
                                            <p class="text-dark small mb-0">Webhook Status: <span class="text-muted">@if($item->is_successful_plugin_callback)Success @else Failed @endif</span></p>
                                        @endif
                                        @if ($item->target && $item->target instanceof \App\Business\RecurringBilling && $item->target->payment_provider === \App\Enumerations\PaymentProvider::DBS_SINGAPORE)
                                            <p class="text-dark small mb-0">Bill Reference: <span class="text-muted">{{ $item->target->dbs_dda_reference }}</span></p>
                                        @endif
                                        @if ($item->payment_provider_charge_method === 'card' && isset($item->data['source']['card']['country']))
                                            @php($country = strtolower($item->data['source']['card']['country']))
                                            <p class="text-dark small mb-0">Card Country: <span class="text-muted">@lang('misc.country.'.$country)</span></p>
                                        @endif

                                        @if($item->display('store_url'))
                                            <p class="text-dark small mb-0">Store URL: {{ $item->display('store_url') }}</p>
                                        @endif

                                        @switch ($item->status)
                                            @case ('succeeded')
                                            <p class="text-dark small mb-0">All Inclusive Fee: <span class="text-muted">{{ $item->display('all_inclusive_fee') }}{{ ($originalFee = $item->display('all_inclusive_fee_original_currency')) ? ' ('.$originalFee.')' : '' }}</span></p>
                                            <p class="text-dark small mb-0">Collected at {{ $item->closed_at->format('h:ia \o\n F d, Y (l)') }}</p>
                                            @if($item->refunds->where('is_campaign_cashback',1)->count())
                                                <span class="badge badge-success">Succeeded (Campaign cashback)</span>
                                            @elseif($item->refunds->where('is_cashback',1)->count())
                                                <span class="badge badge-success">Succeeded (with cashback)</span>
                                            @elseif ($item->amount - ($item->balance ?? 0) !== $item->amount)
                                                <span class="badge badge-success">Partially Refunded</span>
                                            @else
                                                <span class="badge badge-success">Succeeded</span>
                                            @endif
                                            @if (isset($item->is_confirmed) && is_bool($item->is_confirmed) && $item->is_confirmed)
                                                <span class="badge badge-success">Confirmed</span>
                                            @endif
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
                                                @break
                                                @case (\App\Business\RecurringBilling::class)
                                                <span class="badge badge-info">Recurring Plan</span>
                                                @break
                                            @endswitch
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
            @if(!$currentBusinessUser->isCashier())
                @include('custom-pagination')
            @endif
            <business-help-guide :page_type="'charges'"></business-help-guide>
        </div>
        <div class="col-md-3 col-lg-4">
            <business-charge-export
                :current_business_user="{{ json_encode($currentBusinessUser) }}"
            ></business-charge-export>
        </div>
    </div>
@endsection

@push('body-stack')
    <script>
        window.Business = @json($business);
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
