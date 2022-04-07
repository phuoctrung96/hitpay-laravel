@extends('layouts.business', [
    'title' => 'HitPay Balance'
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
                    <span class="float-right text-right mb-0">
                        <div class="small font-weight-light text-muted text-uppercase">Total Balance</div>
                        @if ($totalBalance < 0)
                            <span class="h4 font-weight-bold text-danger">- {{ strtoupper($currency) }} {{ getFormattedAmount($currency, abs($totalBalance), false) }}</span>
                        @elseif ($totalBalance > 0)
                            <span class="h4 font-weight-bold text-success">{{ strtoupper($currency) }} {{ getFormattedAmount($currency, $totalBalance, false) }}</span>
                        @else
                            <span class="h4 font-weight-bold">{{ strtoupper($currency) }} {{ getFormattedAmount($currency, $totalBalance, false) }}</span>
                        @endif
                    </span>
                    <h3 class="font-weight-bold mb-3">Balance - {{ strtoupper($currency) }}</h3>
                    <p class="mb-0">Payout daily automatically.</p>
                </div>
                @if ($paginator->count())
                    @foreach ($paginator as $item)
                        <div class="card-body bg-light border-top p-4">
                            @include('dashboard.business.balance.components.transaction', compact('item'))
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

            @include('custom-pagination')
        </div>
    </div>
@endsection
