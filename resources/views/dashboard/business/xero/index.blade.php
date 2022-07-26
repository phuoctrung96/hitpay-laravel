@extends('layouts.business', [
    'title' => 'Products'
])

@section('business-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 main-content">
            @if(!isset($business->xero_refresh_token))
            <div class="card-body p-4">
                <h2 class="text-primary mb-3 title">Xero Integration</h2>
                <p>Automatically sync your HitPay sales, fees and refund data to Xero</p>
                <a class="btn" href="{{ route('dashboard.business.integration.xero.login', $business->getKey()) }}">
                    <img src="/images/connect-blue.svg" alt="">
                </a>
                <p class="mt-4">Get 8%  off bookkeeping fees from our partner OSOME by quoting HITPAY18 at <a href="https://my.osome.com">https://my.osome.com</a> </p>
            </div>
            <business-help-guide :page_type="'xero'"></business-help-guide>
            <div class="modal fade in" id="disconnectedModal" tabindex="-1" role="dialog" aria-labelledby="disconnectedModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="disconnectedModalLabel">Disconnect from Xero</h5>
                            <button id="closeBtn" type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="model-body">
                            <div class="container">
                                <div class="row">
                                    <div class="col-md-12 pb-4">
                                        <p class="pt-2">Your account was successfully disconnected from Xero</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

           @else
            <xero-account-settings></xero-account-settings>
            <div class="card border-0 shadow-sm">
                @if(session('success_message'))
                    <div class="alert alert-success border-left-0 border-right-0 rounded-0 alert-dismissible fade show" role="alert">
                        {{ session('success_message') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
                <div class="card-body p-4">
                    <h2 class="text-primarytitle">Feed History</h2>
                </div>
                <div class="card-body border-top px-4 py-2">
                    @php($last = ($paginator->currentPage() - 1) * $paginator->perPage())
                    @php($count = count($paginator->items()))
                    @php($from = $count > 0 ? $last + 1 : $last)
                    <p class="small text-muted mb-0">Showing result from {{ number_format($from) }} to {{ number_format($last + count($paginator->items())) }}</p>
                </div>
                @if ($paginator->count())
                    @foreach ($paginator as $item)
                        <div class="card-body bg-light border-top p-4">
                            <div class="media">
                                <div class="media-body">
                                    <span class="text-dark small  float-right">Synced 6:00 AM, {{date('F d, Y', strtotime($item->feed_date))}}  </span>
                                    <span class="font-weight-bold">Xero Daily Feed {{date('F d, Y', strtotime($item->feed_date))}} Data</span>
                                    @if ($item->sales_count)
                                        <p class="text-dark small mb-0">{{ $item->sales_count }} Sales transactions synced
                                    @endif
                                    @if ($item->refund_count)
                                        <p class="text-dark small mb-0">{{ $item->refund_count }} Refunds
                                    @endif
                                    @if ($item->fee_count)
                                        <p class="text-dark small mb-0">{{ $item->fee_count }} Fees
                                    @endif
                                </div>
                            </div>
                        </div>
                @endforeach
                @else
                    <div class="card-body bg-light border-top p-4">
                        <div class="text-center text-muted py-4">
                            <p><i class="fa fas fa-rss fa-4x"></i></p>
                            <p class="small mb-0">- No feeds found -</p>
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
            @endif
        </div>
    </div>
    @push('body-stack')
        <script>
            window.pluginProviders = @json($pluginProviders);
            window.xeroAccountTypes = @json(\App\Business\Xero::XERO_ACCOUT_TYPES);
            window.xeroInvoiceGrouping = @json(\App\Business\Xero::INVOICE_GROUPING_VARIANTS);
            window.xeroAccounts = @json($xeroAccounts);
            window.bankAccounts = @json($bankAccounts);
            window.xeroBrandingThemes = @json($brandingThemes);
            window.showDisconnetPopup = {{!isset($business->xero_refresh_token) ? 1 : 0}};
        </script>
    @endpush
@endsection
