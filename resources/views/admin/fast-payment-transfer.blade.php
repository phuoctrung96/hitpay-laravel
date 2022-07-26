@extends('layouts.admin', [
    'title' => 'Fast Payment Transfer'
])

@section('admin-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 main-content">
            <div class="btn-group btn-group-sm mb-3 w-100 shadow-sm">
                <a class="btn col {{ $status === 'pending' ? 'active btn-outline-primary' : 'btn-light' }}" href="{{ route('admin.transfer.fast-payment.index', [
                    'status' => 'pending',
                ]) }}">Pending</a>
                <a class="btn col {{ $status === 'succeeded' ? 'active btn-outline-primary' : 'bg-light' }}" href="{{ route('admin.transfer.fast-payment.index', [
                    'status' => 'succeeded',
                ]) }}">Succeeded</a>
            </div>
            <div class="form-group">
                <form class="input-group input-group-lg" action="{{ route('admin.transfer.fast-payment.index') }}">
                    <input class="form-control border-0 shadow-sm" placeholder="Search by ID / Business ID ({{ $status }})" title="Search by ID / Business ID" name="keyword" value="{{ request('keyword') }}">
                    <input type="hidden" name="status" value="{{ $status }}">
                    <div class="input-group-append">
                        <button class="btn btn-primary shadow-sm"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <h2 class="text-primary mb-0 title">HitPay Payouts</h2>
                </div>
                <div class="card-body border-top px-4 py-2">
                    <p class="small text-muted mb-0">Showing the latest {{ $paginator->count() }} results</p>
                </div>
                @if ($paginator->count())
                    @foreach ($paginator as $payout)
                        @if ($payout['status'] === 'request_pending')
                            <a class="hoverable" href="{{ route('admin.transfer.fast-payment.get', [
                                $payout->getKey()
                            ]) }}">
                        @endif
                        <div class="card-body bg-light border-top p-4">
                            <span class="float-right">{{ getFormattedAmount($payout->currency, $payout->amount) }}</span>
                            <p class="font-weight-bold mb-2">{{ $payout->business->getName() }}</p>
                            <p class="small font-weight-bold mb-2">{{ $payout->remark }}</p>
                            <p class="text-dark small mb-2"><span class="text-muted"># {{ $payout->id }}</span></p>
                            @if ($payout['status'] === 'succeeded' || $payout['status'] === 'succeeded_manually')
                                <p class="text-dark small mb-0">Transferred Date:
                                    <span class="text-muted">{{ $payout['updated_at'] }}</span></p>
                            @endif
                            @php([
                                $bankSwiftCode,
                                $bankAccountNumber,
                            ] = explode('@', $payout->payment_provider_account_id))
                            <p class="text-dark small mb-2">Payout Destination:
                                <span class="text-muted">{{ \App\Business\Transfer::$availableBankSwiftCodes[$bankSwiftCode] ?? $bankSwiftCode }} ({{ $bankAccountNumber }})</span>
                            </p>
                            @if ($payout['status'] === 'succeeded')
                                @if (is_int($payout['counter']))
                                    <span class="small font-weight-bold text-success">Paid (Retried)</span>
                                @else
                                    <span class="small font-weight-bold text-success">Paid</span>
                                @endif
                            @elseif ($payout['status'] === 'succeeded_manually')
                                <span class="small font-weight-bold text-info">Paid manually</span>
                            @else
                                <span class="small font-weight-bold text-warning">Pending</span>
                            @endif
                        </div>
                        @if ($payout['status'] === 'request_pending')
                            </a>
                        @endif
                    @endforeach
                @else
                    <div class="card-body bg-light border-top p-4">
                        <div class="text-center text-muted py-4">
                            <p><i class="fas fa-money-check-alt fa-4x"></i></p>
                            <p class="small mb-0">- No payout found -</p>
                        </div>
                    </div>
                @endif
                <div class="card-body border-top pt-0">
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
        </div><div class="col-md-3 col-lg-4">
            <admin-business-fast-payout-export></admin-business-fast-payout-export>
        </div>
    </div>
@endsection

@push('body-stack')
    <script>
        window.Business = null;
    </script>
@endpush
