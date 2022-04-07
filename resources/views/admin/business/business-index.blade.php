@extends('layouts.admin', [
    'title' => 'Charges'
])

@section('admin-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 main-content">
            <div class="btn-group btn-group-sm mb-3 w-100 shadow-sm">
                <a class="btn col {{ $risk_level === \App\Enumerations\Business\ComplianceRiskLevel::LOW_RISK ? 'active btn-outline-primary' : 'bg-light' }}" href="{{ route('admin.business.index', [
                    'risk_level' => \App\Enumerations\Business\ComplianceRiskLevel::LOW_RISK,
                ]) }}">Low Risk</a>
                <a class="btn col {{ $risk_level === \App\Enumerations\Business\ComplianceRiskLevel::MEDIUM_RISK ? 'active btn-outline-primary' : 'bg-light' }}" href="{{ route('admin.business.index', [
                    'risk_level' => \App\Enumerations\Business\ComplianceRiskLevel::MEDIUM_RISK,
                ]) }}">Medium Risk</a>
                <a class="btn col {{ $risk_level === \App\Enumerations\Business\ComplianceRiskLevel::HIGH_RISK ? 'active btn-outline-primary' : 'bg-light' }}" href="{{ route('admin.business.index', [
                    'risk_level' => \App\Enumerations\Business\ComplianceRiskLevel::HIGH_RISK,
                ]) }}">High Risk</a>
            </div>
            <div class="btn-group btn-group-sm mb-3 w-100 shadow-sm">
                <a class="btn col {{ $verification_type === 'manual' ? 'active btn-outline-primary' : 'bg-light' }}" href="{{ route('admin.business.index', [
                    'verification_type' => 'manual',
                ]) }}">Manual verification</a>
                <a class="btn col {{ $verification_type === 'myinfo' ? 'active btn-outline-primary' : 'bg-light' }}" href="{{ route('admin.business.index', [
                    'verification_type' => 'myinfo',
                ]) }}">Myinfo verification</a>
            </div>
            <div class="form-group">
                <form class="input-group input-group-lg" action="{{ route('admin.business.index') }}">
                    <input class="form-control border-0 shadow-sm" placeholder="Search by name" title="Search by name" name="keywords" value="{{ request('keywords') }}">
                    <div class="input-group-append">
                        <button class="btn btn-primary shadow-sm"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>
            <div class="custom-control custom-switch mb-3">
                <input type="checkbox" class="custom-control-input" id="order-related-check" onchange="check(this, 'myinfo_unverified')"{{ request('verification_status') === 'myinfo_unverified' ? ' checked' : '' }}>
                <label class="custom-control-label" for="order-related-check">Show unverified business only</label>
            </div>
            <div class="custom-control custom-switch mb-3">
                <input type="checkbox" class="custom-control-input" id="pending-business" onchange="check(this, 'pending')"{{ request('verification_status') === 'pending' ? ' checked' : '' }}>
                <label class="custom-control-label" for="pending-business">Show pending verification businesses</label>
            </div>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <h2 class="text-primary mb-0 title">Businesses</h2>
                </div>
                <div class="card-body border-top px-4 py-2">
                    @php($last = ($paginator->currentPage() - 1) * $paginator->perPage())
                    @php($count = count($paginator->items()))
                    @php($from = $count > 0 ? $last + 1 : $last)
                    <p class="small text-muted mb-0">Showing result from {{  number_format($from) }} to {{ number_format($last + count($paginator->items())) }}</p>
                </div>
                @if ($paginator->count())
                    @foreach ($paginator as $item)
                        <a class="hoverable" href="{{ route('admin.business.show', [
                            $item->getKey(),
                        ]) }}">
                            <div class="card-body bg-light border-top p-4">
                                <div class="media">
                                    <div class="media-body">
                                        <p class="font-weight-bold mb-2">{{ $item->getName() }}</p>
                                        <p class="text-dark small mb-2">
                                            <span class="text-muted"># {{ $item->getKey() }}</span></p>
                                        <p class="text-dark small mb-0">Login Email:
                                            <span class="text-muted">{{ $item->owner->email }}</span></p>
                                        <p class="text-dark small mb-0">Verification Status:
                                            {{$item->verifications()->latest()->first() ? $item->verifications()->latest()->first()->status : 'No verification'}}
                                        </p>
                                        <p class="text-dark small mb-0">Business Email:
                                            <span class="text-muted">{{ $item->email }}</span></p>
                                        @php($wallets = $item->wallets->groupBy('currency'))
                                        <p class="text-dark small mb-0">Total Balance:
                                            @if ($wallets->count())
                                                @foreach($wallets as $currency => $wallet)
                                                    @php($balance = $item->wallets->sum('balance'))
                                                    <br>
                                                    @if ($balance < 0)
                                                        <span class="text-danger font-weight-bold">- {{ getFormattedAmount($currency, abs($balance)) }}</span>
                                                    @elseif ($balance > 0)
                                                        <span class="text-success font-weight-bold">{{ getFormattedAmount($currency, $balance) }}</span>
                                                    @else
                                                        <span class="font-weight-bold">{{ getFormattedAmount($currency, $balance) }}</span>
                                                    @endif
                                                @endforeach
                                            @else
                                                -
                                            @endif
                                        </p>
                                        @php($paymentProviders = $item->paymentProviders->whereIn('payment_provider', \App\Enumerations\PaymentProvider::listConstants()))
                                        @if ($paymentProviders->count())
                                            <p class="text-dark small mb-0">Payment Providers:</p>
                                            <ul class="text-muted small mb-0">
                                                @foreach ($paymentProviders as $provider)
                                                    @if ($provider->payment_provider === 'stripe_sg')
                                                        <li>Stripe Singapore</li>
                                                    @elseif ($provider->payment_provider === 'stripe_my')
                                                        <li>Stripe Malaysia</li>
                                                    @elseif ($provider->payment_provider === 'dbs_sg')
                                                        <li>PayNow Online by DBS</li>
                                                    @else
                                                        <li>{{ $provider->payment_provider }}</li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        @else
                                            <p class="font-weight-bold text-danger small mb-0">Payment provider not set</p>
                                        @endif
                                        @if ($item->platform_enabled)
                                            <p class="font-weight-bold badge badge-success mb-0">Platform feature enabled</p>
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
                            <p class="small mb-0">- No business found -</p>
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
            <button class="btn btn-primary" data-toggle="modal" data-target="#exportModal">Export Businesses</button>
            <div class="modal fade" id="exportModal" tabindex="-1" role="dialog" aria-labelledby="exportModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exportModalLabel">Export</h5>
                            <button id="closeBtn" type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form method="post" action="{{ route('admin.business.export') }}">
                                @csrf
                                <button id="downloadBtn" type="submit" class="btn btn-primary">
                                    Send to email
                                </button>
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
        function check(trigger, status) {
            let current = new URL(window.location.href);
            let query = current.search;
            let params = new URLSearchParams(query);

            if ($(trigger).is(':checked')) {
                params.set('verification_status', status);
            } else {
                params.set('verification_status', null);
            }

            current.search = params.toString();
            window.location = current.toString();
        }
    </script>
@endpush
