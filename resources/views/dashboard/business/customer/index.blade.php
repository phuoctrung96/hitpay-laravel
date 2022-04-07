@extends('layouts.business', [
    'title' => 'Customers'
])

@section('business-content')
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-8 main-content">
            <div class="form-group">
                <form class="input-group input-group-lg" action="{{ route('dashboard.business.customer.index', [
                    'business_id' => $business->getKey(),
                ]) }}">
                    <input class="form-control border-0 shadow-sm" placeholder="Search By Customer Last Name / Email" title="Search Customer" name="keywords" value="{{ $keywords = request('keywords') }}">
                    <div class="input-group-append">
                        <button class="btn btn-primary shadow-sm"><i class="fas fa-search"></i></button>
                    </div>
                </form>
                <span class="small text-muted">Separate keywords by space, maximum 3 keywords will be processed.</span>
            </div>
            <div class="card shadow-sm mb-3">
                <div class="card-body p-4">

                    <p>Record your customer and check their records.</p>
                    <a class="btn btn-primary" href="{{ route('dashboard.business.customer.create', $business->getKey()) }}">
                        <i class="fas fa-plus mr-2"></i> Add Customer
                    </a>
                    <a class="btn btn-secondary"
                       href="{{ route('dashboard.business.customer.bulk', $business->getKey()) }}">
                        <i class="fas fa-plus mr-2"></i> Add Customers In Bulk
                    </a>
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
                    <p class="small text-muted mb-0">Showing result from {{ number_format($from) }} to {{ number_format($last + count($paginator->items())) }}</p>
                </div>
                <div class="card-body border-top py-2 action-panel">
                    <input type="checkbox" class="all-status-checkbox mr-3" >
                    <button class="btn btn-danger btn-sm del-bulk-customer">Delete Customers</button>
                </div>
                @if ($paginator->count())
                    @foreach ($paginator as $item)
                        <div class="card-body border-top py-2 action-panel">
                            <div class="media">
                                <input type="checkbox" class="customer-checkbox mr-3" value="{{ $item->getKey() }}">
                                <a class="hoverable" href="{{ route('dashboard.business.customer.show', [
                                    $business->getKey(),
                                    $item->getKey()
                                ]) }}">
                                    <div class="media-body">
                                        <p class="font-weight-bold mb-2">{{ $item->name ?? $item->email }}</p>
                                        <p class="text-dark small mb-2"><span class="text-muted"># {{ $item->getKey() }}</span></p>
                                        <p class="text-dark small mb-0">Email Address: <span class="text-muted">{{ $item->email }}</span></p>
                                        <p class="text-dark small mb-0">Phone Number: <span class="text-muted">{{ $item->phone_number }}</span></p>
                                        <p class="text-dark small mb-0">Address: <span class="text-muted">{{ implode(', ', array_filter([
                                            $item->street,
                                            $item->city,
                                            $item->state,
                                            strtoupper($item->country),
                                        ])) }}</span></p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="card-body bg-light border-top p-4">
                        <div class="text-center text-muted py-4">
                            <p><i class="fa fas fa-user-friends fa-4x"></i></p>
                            <p class="small mb-0">- No customer found -</p>
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

                @for ($i = 1; $i <= $paginator->lastPage(); $i++)
                    <li class="page-item{{ ($paginator->currentPage() == $i) ? ' disabled' : '' }}">
                        <a class="page-link" href="{{ $paginator->url($i) }}">{{ $i }}</a>
                    </li>
                @endfor

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
            <business-help-guide :page_type="'customers'"></business-help-guide>
        </div>
        <business-customer-export></business-customer-export>      
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

        document.addEventListener("DOMContentLoaded", () => {
            let deleteState = false;
            $(".all-status-checkbox").click(function() {
                $('input.customer-checkbox').not(this).prop('checked', this.checked);
            });
            $(".del-bulk-customer").click(function() {
                if (deleteState) {
                    return;
                }

                let customerIds = [];
                $('input.customer-checkbox:checked').each(function() {
                    customerIds.push($(this).val());
                });
                if (customerIds.length) {
                    deleteState = true;
                    axios.post("{{ route('dashboard.business.customer.delete-bulk', ['business_id' => $business->getKey()]) }}", {
                        customer_ids: customerIds
                    }).then(({data}) => {
                        window.location.href = data.redirect_url;
                    }).catch(() => {
                        deleteState = false;
                        alert('Failed to delete selected customers');
                    });
                } else {
                    alert('Nothing to delete');
                }
            });
        });
    </script>
@endpush