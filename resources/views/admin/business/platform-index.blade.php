@extends('layouts.admin', [
    'title' => 'Terminals - '.$business->getName(),
])

@section('admin-content')
    <input id="platform-key" class="d-none" value="{{ $business->platform_key }}" title="Platform Key" disabled>
    <div class="row">
        <div class="col-12 col-md-9 col-lg-8 mb-4">
            <a href="{{ route('admin.business.show', $business->getKey()) }}">
                <i class="fas fa-reply fa-fw mr-3"></i> Back to {{ $business->getName() }}</a>
        </div>
        <div class="col-md-9 col-lg-8 main-content">
            <div class="form-group">
                <form class="input-group input-group-lg" action="{{ route('admin.business.platform.index', [
                    $business->getKey(),
                ]) }}">
                    <input class="form-control border-0 shadow-sm" placeholder="Search By ID Or Plugin Provider Reference Or Email" title="Search Charge" name="keyword" value="{{ request('keyword') }}">
                    <div class="input-group-append">
                        <button class="btn btn-primary shadow-sm"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <p class="text-uppercase text-muted mb-0">{{ $business->getName() }}</p>
                    <h2 class="text-primary mb-3 title">Platform</h2>
                    @if ($business->platform_enabled)
                        <p class="font-weight-bold">Platform Key</p>
                        <pre>{{ $business->platform_key }}</pre>
                        <p>
                            <a id="copyButton" href="#">Click here to copy platform key</a>
                        </p>
                        <a class="text-danger small" href="#" data-toggle="modal" data-target="#regenerateModal">Regenerate Platform Key</a><br>
                        <a class="text-danger small" href="#" data-toggle="modal" data-target="#deleteModal">Disable Platform Feature</a>
                    @else
                        <form method="post" action="{{ route('admin.business.platform.enable', $business->getKey()) }}">
                            @csrf @method('put')
                            <button type="submit" class="btn btn-success btn-sm">Enable Feature</button>
                        </form>
                    @endif
                </div>
                @if ($successMessage = session('success_message'))
                    @php($border_top = false)
                    <div class="alert alert-success border-left-0 border-right-0 rounded-0 mb-0">
                        {{ $successMessage }}
                    </div>
                @elseif ($dangerMessage = session('danger_message'))
                    @php($border_top = false)
                    <div class="alert alert-danger border-left-0 border-right-0 rounded-0 mb-0">
                        {{ $dangerMessage }}
                    </div>
                @endif
                <div class="card-body px-4 {{ $border_top ?? true ? 'pt-0' : 'pt-3' }} pb-3">
                    <label class="text-uppercase text-muted mb-0">Charges via Platform</label>
                </div>
                <div class="card-body border-top px-4 py-2">
                    @php($last = ($paginator->currentPage() - 1) * $paginator->perPage())
                    @php($count = count($paginator->items()))
                    @php($from = $count > 0 ? $last + 1 : $last)
                    <p class="small text-muted mb-0">Showing result from {{  number_format($from) }} to {{ number_format($last + count($paginator->items())) }}</p>
                </div>
                @if ($paginator->count())
                    @foreach ($paginator as $item)
                        <div class="card-body bg-light border-top p-4">
                            <div class="media">
                                <div class="media-body">
                                    <span class="float-right">{{ $item->display('amount') }}</span>
                                    <p class="font-weight-bold mb-2">{{ $item->business->name }}</p>
                                    <p class="text-dark small mb-2">
                                        <span class="text-muted"># {{ $item->getKey() }}</span></p>
                                    @if ($item->remark)
                                        <p class="text-dark mb-2">{{ $item->remark }}</p>
                                    @endif
                                    <p class="small mb-0">Customer Name:
                                        <span class="text-muted">{{ $item->customer_name ?? '-' }}</span></p>
                                    <p class="small mb-0">Customer Email:
                                        <span class="text-muted">{{ $item->customer_email ?? '-' }}</span></p>
                                    <p class="small mb-2">Customer Phone Number:
                                        <span class="text-muted">{{ $item->customer_phone_number ?? '-' }}</span></p>
                                    <p class="small mb-0">Channel:
                                        <span class="text-muted">{{ ucwords(str_replace('_', ' ', $item->channel)) }}</span>
                                    </p>
                                    <p class="small mb-0">HitPay Reference ID: <span class="text-muted">{{ ucwords(str_replace('_', ' ', $item->payment_provider)) }} <span class="font-weight-bold">{{ $item->payment_provider_charge_id }}</span></span>
                                    </p>
                                    @if ($item->channel === \App\Enumerations\Business\Channel::PAYMENT_GATEWAY)
                                        <p class="small mb-0">Plugin:
                                            <span class="text-muted">{{ ucwords(str_replace('_', ' ', $item->plugin_provider)) }}</span>
                                        </p>
                                        <p class="small mb-0">Reference:
                                            <span class="text-muted">{{ $item->plugin_provider_reference }}</span></p>
                                        @if ($item->plugin_provider_order_id)
                                            <p class="small mb-0">Order ID:
                                                <span class="text-muted">{{ $item->plugin_provider_order_id }}</span>
                                            </p>
                                        @endif
                                        <p class="small mb-0">Callback Status:
                                            <span class="text-muted">{{ $item->is_successful_plugin_callback ? 'Succeeded' : 'Failed' }}</span>
                                        </p>
                                    @endif
                                    <p class="text-dark small mb-0">Payment Method: <span class="text-muted">
                                    {!! \App\Enumerations\Business\PaymentMethodType::displayName($item->payment_provider_charge_method) !!}
                                    </span></p>
                                    @if ($item->payment_provider_charge_method === 'card' && isset($item->data['source']['card']['country']))
                                        @php($country = strtolower($item->data['source']['card']['country']))
                                        <p class="text-dark small mb-0">Card Country:
                                            <span class="text-muted">@lang('misc.country.'.$country)</span></p>
                                    @endif
                                    @switch ($item->status)
                                        @case ('succeeded')
                                        <p class="text-dark small mb-0">All Inclusive Fee:
                                            <span class="text-muted">{{ $item->display('all_inclusive_fee') }}</span>
                                        </p>
                                        <p class="text-dark small mb-0">Commission:
                                            <span class="text-muted">{{ $item->display('commission') }}</span>
                                        </p>
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
            <div class="modal fade" id="regenerateModal" tabindex="-1" role="dialog" aria-labelledby="regenerateModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title font-weight-bold text-danger" id="regenerateModalLabel">Warning!</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p id="warning-text">Are you sure you want to regenerate platform key for {{ $business->getName() }}?</p>
                            <form method="post" action="{{ route('admin.business.platform.rekey', $business->getKey()) }}">
                                @csrf @method('put')
                                <button type="submit" class="btn btn-danger btn-sm">Regenerate Key</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title font-weight-bold text-danger" id="deleteModalLabel">Warning!</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p id="warning-text">Are you sure you want to disable platform feature for {{ $business->getName() }}?</p>
                            <form method="post" action="{{ route('admin.business.platform.enable', $business->getKey()) }}">
                                @csrf @method('delete')
                                <button type="submit" class="btn btn-danger btn-sm">Disable Feature</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('body-stack')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var copyButton = document.getElementById('copyButton');
            if (copyButton) {
                copyButton.addEventListener('click', function () {
                    event.preventDefault();

                    target = document.getElementById('platform-key');

                    target.classList.remove('d-none');
                    target.removeAttribute('disabled');

                    var currentFocus = document.activeElement;

                    target.focus();
                    target.setSelectionRange(0, target.value.length);

                    var succeed;

                    try {
                        succeed = document.execCommand('copy');

                        alert('Platform Key Copied');
                    } catch (e) {
                        succeed = false;
                    }

                    if (currentFocus && typeof currentFocus.focus === 'function') {
                        currentFocus.focus();
                    }

                    target.setAttribute('disabled', true);
                    target.classList.add('d-none');

                    return succeed;
                });
            }
        });
    </script>
@endpush
