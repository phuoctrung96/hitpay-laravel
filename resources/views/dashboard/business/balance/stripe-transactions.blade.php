@extends('layouts.business', [
    'title' => 'Card Transactions'
])

@section('business-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 mb-4">
            <a href="{{ route('dashboard.business.balance.homepage', [
                $business->getKey(),
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to All Balance</a>
        </div>
        <div class="col-12 col-xl-9 main-content">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <h3 class="font-weight-bold mb-0">Card Transactions</h3>
                </div>
                @if ($balanceTransactions->count())
                    @foreach ($balanceTransactions as $item)
                        <div class="card-body bg-light border-top p-4">
                            @include('dashboard.business.balance.components.stripe.transaction', compact('item'))
                        </div>
                    @endforeach
                @else
                    <div class="card-body bg-light border-top p-4">
                        <div class="text-center text-muted py-4">
                            <p><i class="fa fas fa-dollar-sign fa-4x"></i></p>
                            <p class="small mb-0">- No Transactions found -</p>
                        </div>
                    </div>
                @endif
                <div class="card-body border-top pt-0 pb-4"></div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="float-left">
                        <form class="form-inline" method="GET" action="{{ url()->current() }}">
                            <label for="perPage">Showing</label>
                            <select class="form-control ml-3" onchange="paginationPerPageChanged(this);" id="perPage" name="perPage">
                                @foreach(\App\Helpers\Pagination::AVAILABLE_PAGE_NUMBER as $perPageNumber)
                                    <option value="{{ $perPageNumber }}" @if ($perPage === $perPageNumber) selected @endif>{{ $perPageNumber }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </div>
                <div class="col-md-8 float-right">
                    <ul class="pagination float-right">
                        <li class="page-item">
                            <a class="page-link" href="{{ route('dashboard.business.balance.stripe.transactions', [
                                $business->id,
                                'ending_before' => $balanceTransactionFirstId,
                                'perPage' => $perPage,
                             ]) }}" rel="prev" aria-label="@lang('pagination.previous')">@lang('pagination.previous')</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="{{ route('dashboard.business.balance.stripe.transactions', [
                                $business->id,
                                'start_after' => $balanceTransactionLastId,
                                'perPage' => $perPage,
                             ]) }}" rel="prev" aria-label="@lang('pagination.next')">@lang('pagination.next')</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('body-stack')
    <script>
        function paginationPerPageChanged(trigger)
        {
            let current = new URL(window.location.href);
            let query = current.search;
            let params = new URLSearchParams(query);

            let perPage = $(trigger).val();

            params.set('perPage', perPage);

            current.search = params.toString();
            window.location = current.toString();
        }
    </script>
@endpush
