@extends('layouts.business', [
    'title' => 'Recurring Plan - '.$recurringPlan->name,
])

@section('business-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 mb-4">
            <a href="{{ route('dashboard.business.recurring-plan.index', [
                'business_id' => $business->getKey(),
                'status' => $recurringPlan->status,
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to Recurring Plans</a>
        </div>
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card shadow-sm mb-3">
                <div class="card-body p-4">
                    <label class="small text-uppercase text-muted mb-3">Recurring Plan
                        # {{ $recurringPlan->getKey() }}</label>
                    <h2 class="text-primary mb-3 title">{{ $recurringPlan->name }}</h2>
                    @if ($recurringPlan->description)
                        <p class="text-muted small">{{ $recurringPlan->description }}</p>
                    @endif
                    <p class="text-dark small mb-0">Name: <span
                            class="text-muted">{{ $recurringPlan->customer_name ?? '-' }}</span></p>
                    <p class="text-dark small mb-0">Email Address: <span
                            class="text-muted">{{ $recurringPlan->customer_email ?? '-' }}</span></p>
                    <p class="text-dark small mb-0">Phone Number: <span
                            class="text-muted">{{ $recurringPlan->customer_phone_number ?? '-' }}</span></p>
                    <p class="text-dark small mb-3">Address: <span class="text-muted">{{ implode(', ', array_filter([
                        $recurringPlan->customer_street,
                        $recurringPlan->customer_city,
                        $recurringPlan->customer_state,
                        strtoupper($recurringPlan->customer_country),
                    ])) ?: '-' }}</span></p>
                    <p class="text-dark small mb-0">Price: <span
                            class="text-muted">{{ $recurringPlan->getPrice() }}</span></p>
                    <p class="text-dark small mb-0">Recurring Cycle: <span
                            class="text-muted">{{ ucfirst($recurringPlan->cycle) }}</span></p>
                    @if($recurringPlan->status === \App\Enumerations\Business\RecurringPlanStatus::ACTIVE)
                        @if ($recurringPlan->payment_provider === \App\Enumerations\PaymentProvider::DBS_SINGAPORE)
                            <p class="text-dark small mb-0">Payment Method: <span class="text-muted">Direct Debit</span>
                            </p>
                            <p class="text-dark small mb-0">Bill Reference: <span class="text-muted">{{ $recurringPlan->dbs_dda_reference }}r</span>
                            </p>
                        @elseif ($recurringPlan->payment_provider === \App\Enumerations\PaymentProvider::STRIPE_SINGAPORE)
                            @php($card = $recurringPlan->data['stripe']['payment_method']['card'] ?? [])
                            @php($brand = isset($card['brand']) ? ucwords($card['brand']) : 'Unknown')
                            @php($country = isset($card['country']) ? (Lang::has('misc.country.'.$card['country']) ? Lang::get('misc.country.'.$card['country']) : $card['country']) : 'Unknown Country')
                            <p class="text-dark small mb-0">Payment Method: <span class="text-muted">Card Payment</span>
                            </p>
                            <p class="text-dark small mb-0">Attached Card: <span class="text-muted">{{ $brand }} (****{{ $card['last4'] ?? '' }}, {{ $country }})</span>
                            </p>
                        @else
                            <p class="text-dark small mb-0">Payment Method: <span class="text-muted">Unknown</span></p>
                        @endif
                    @endif
                    <p class="text-dark small mb-3">Created
                        at: {{ $recurringPlan->created_at->format('h:ia \o\n F d, Y (l)') }}</p>
                    @if ($recurringPlan->times_to_be_charged)
                        <p class="text-muted small">Charged Times: <span class="text-success font-weight-bold">{{ $recurringPlan->times_charged }} / {{ $recurringPlan->times_to_be_charged }}</span>
                        </p>
                    @endif
                    @switch($recurringPlan->status)
                        @case('active')
                        <p><span class="badge badge-lg badge-success">Active</span></p>
                        <p class="text-dark small mb-0">Next charge
                            at: {{ $recurringPlan->expires_at->format('d \o\f F, Y') }}</p>
                        @break
                        @case('completed')
                        <p class="mb-0"><span class="badge badge-lg badge-success">Completed</span></p>
                        @break
                        @case('scheduled')
                        <p><span class="badge badge-lg badge-warning">Pending</span></p>
                        <p class="text-dark small mb-0">Automatically cancel
                            at: {{ $recurringPlan->expires_at->format('d \o\f F, Y') }}</p>
                        @break
                        @case('canceled')
                        <p><span class="badge badge-lg badge-danger">Canceled</span></p>
                        @break
                        @default
                        <p><span class="badge badge-lg badge-warning">Unknown</span></p>
                    @endswitch
                    @if(session('success_message'))
                        <div
                            class="alert alert-success rounded-0 border-left-0 border-right-0 alert-dismissible fade show mb-0 mt-3"
                            role="alert">
                            {{ session('success_message') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
                    @if ($recurringPlan->status === 'active' || $recurringPlan->status === 'scheduled')
                        <hr>
                        <p class="mt-3">
                            <business-recurring-plan-send></business-recurring-plan-send>
                        </p>
                        <p class="small text-muted">{{ route('recurring-plan.show', [
                            'business_id' => $recurringPlan->business_id,
                            'recurring_plan_id' => $recurringPlan->id,
                        ]) }}</p>
                        <p><a id="copyButton" class="small" href="#">Click here to copy link</a></p>
                        <p class="mb-0 mt-3">
                            <a class="small text-danger" data-toggle="modal" data-target="#confirmCancelModal" href="#">Cancel
                                Plan</a>
                        </p>
                        <div id="confirmCancelModal" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-body">
                                        <h5 class="modal-title text-danger font-weight-bold mb-3">
                                            Are you sure you want to cancel?
                                        </h5>
                                        <form method="post" action="{{ route('dashboard.business.recurring-plan.cancel', [
                                            $business->getKey(),
                                            $recurringPlan->getKey(),
                                        ]) }}" onsubmit="freeze(this)">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="btn btn-danger">Yes</button>
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">No
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="card-body border-top px-4 py-2">
                    @php($last = ($paginator->currentPage() - 1) * $paginator->perPage())
                    @php($count = count($paginator->items()))
                    @php($from = $count > 0 ? $last + 1 : $last)
                    <p class="small text-muted mb-0">Showing result from {{ number_format($from) }}
                        to {{ number_format($last + count($paginator->items())) }}</p>
                </div>
                @if ($paginator->count())
                    @foreach ($paginator as $item)
                        <a class="hoverable" href="{{ route('dashboard.business.charge.show', [
                            $business->getKey(),
                            $item->getKey()
                        ]) }}" target="_blank">
                            <div class="card-body bg-light border-top p-4">
                                <div class="media">
                                    <div class="media-body">
                                        <span class="float-right">{{ $item->display('amount') }}</span>
                                        <p class="text-dark">{{ $item->remark }}</p>
                                        <p class="text-dark small mb-2"><span
                                                class="text-muted"># {{ $item->getKey() }}</span></p>
                                        <p class="text-dark small mb-0">Payment Method: <span class="text-muted">
                                            @switch ($item->payment_provider_charge_method)
                                                    @case ('card')
                                                    <i class="far fa-credit-card"></i> Charge Card
                                                    @break
                                                    @case ('alipay')
                                                    <i class="fab fa-alipay"></i> Alipay
                                                    @break
                                                    @case ('wechat')
                                                    <i class="fab fa-weixin"></i> WeChat pay
                                                    @break
                                                    @case ('card_present')
                                                    <i class="fas fa-calculator"></i> Card Reader
                                                    @break
                                                    @case ('cash')
                                                    <i class="fas fa-dollar-sign"></i> Cash
                                                    @break
                                                    @default
                                                    unknown
                                                @endswitch
                                        </span></p>
                                        @switch ($item->status)
                                            @case ('succeeded')
                                            <p class="text-dark small mb-0">Collected
                                                at {{ $item->closed_at->format('h:ia \o\n F d, Y (l)') }}</p>
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
                            <p class="small mb-0">- No charges found for this recurring plan -</p>
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
                        <a class="page-link" href="{{ $paginator->url($paginator->currentPage() - 1) }}"
                           rel="prev">@lang('pagination.previous')</a>
                    </li>
                @endif

                @if ($paginator->currentPage() < $paginator->lastPage())
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->url($paginator->currentPage() + 1) }}"
                           rel="next">@lang('pagination.next')</a>
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
    <input id="store-url" class="d-none" value="{{ route('recurring-plan.show', [
        'business_id' => $recurringPlan->business_id,
        'recurring_plan_id' => $recurringPlan->id,
    ]) }}" title="Store Link" disabled>
    <script>
        window.Business = @json($business);
        window.RecurringPlan = @json($recurringPlan);

        function freeze(element) {
            $('button').prop('disabled', true);
            $(element).find('[type=submit]').append($('<i class="fas fa-spinner fa-spin ml-2">'))
        }

        @if ($recurringPlan->status === 'active' || $recurringPlan->status === 'scheduled')
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('copyButton').addEventListener('click', function () {
                event.preventDefault();

                target = document.getElementById('store-url');

                target.classList.remove('d-none');
                target.removeAttribute('disabled');

                var currentFocus = document.activeElement;

                target.focus();
                target.setSelectionRange(0, target.value.length);

                var succeed;

                try {
                    succeed = document.execCommand('copy');

                    alert('Link Copied');
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
        });
        @endif
    </script>
@endpush
