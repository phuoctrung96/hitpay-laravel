@extends('layouts.business', [
    'title' => 'Payment Links'
])

@section('business-content')
    <div class="row justify-content-center">
        <div class="col-lg-12 main-content">
            <div class="btn-group btn-group-sm mb-3 w-100 shadow-sm">
                <a class="btn col {{ $status === \App\Enumerations\Business\PaymentRequestStatus::ALL
                                            ? 'active btn-outline-primary'
                                            : 'bg-light' }}"
                   href="{{ route('dashboard.business.payment-links.index', [
                        $business->getKey(),
                        'status' => \App\Enumerations\Business\PaymentRequestStatus::ALL,
                    ]) }}">All</a>
                <a class="btn col {{ $status === \App\Enumerations\Business\PaymentRequestStatus::PENDING
                                        ? 'active btn-outline-primary'
                                        : 'btn-light' }}"
                   href="{{ route('dashboard.business.payment-links.index', [
                        $business->getKey(),
                        'status' => \App\Enumerations\Business\PaymentRequestStatus::PENDING,
                    ]) }}">Not Paid</a>
                <a class="btn col {{ $status === \App\Enumerations\Business\PaymentRequestStatus::COMPLETED
                                        ? 'active btn-outline-primary'
                                        : 'btn-light' }}"
                   href="{{ route('dashboard.business.payment-links.index', [
                        $business->getKey(),
                        'status' => \App\Enumerations\Business\PaymentRequestStatus::COMPLETED,
                    ]) }}">Paid</a>
            </div>
            <div class="card shadow-sm mb-3">
                <div class="card-body p-4">
                    <create-payment-link :currency_list="{{json_encode($currencies)}}"
                                         :zero_decimal_list = "{{json_encode($zero_decimal_cur)}}"
                    ></create-payment-link>
                    <p class="text-muted mt-4 mb-0">Default link</p>
                    <div class="input-group" style="width: 75%;">
                        <input type="hidden" id="default_payment_link" value="{{ $business->paymentRequests()->where('is_default', true)->first()->url}}">
                        <input class="form-control" id="input_default_link" readonly="true" value="{{ $business->paymentRequests()->where('is_default', true)->first()->url}}" />
                        <div class="input-group-append">
                            <span class="input-group-text"><a href="javascript:void(0)" class="btn-copy">COPY</a></span>
                        </div>
                        <business-edit-slug />
                    </div>
                    <p class="text-muted mb-0">Accept payment from any customer with any amount using just one click</p>
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
                @if ($paginator->count())
                    @foreach ($paginator as $item)
                            <div class="card-body bg-light border-top p-4">
                                <div class="media">
                                    <div class="media-body">
                                        <p class="text-dark small mb-2"><span class="text-muted"># {{ $item->getKey() }}</span></p>
                                        <p class="text-dark small mb-0">Amount: <span class="text-muted">{{ strtoupper($item->currency).' '. $item->amount }}</span></p>
                                        @if($item->email)<p class="text-dark small mb-0">Email: <span class="text-muted">{{ $item->email }}</span></p>@endif
                                        @if($item->reference_number)<p class="text-dark small mb-0">Reference Number: <span class="text-muted">{{ $item->reference_number }}</span></p>@endif
                                        @if($item->allow_repeated_payments)<p class="text-dark small mb-0">Repeated</p>@endif
                                        <p class="text-dark small mb-0">URL: <a class="text-muted" href="{{$item->url}}">{{ $item->url }}</a></p>
                                        <p class="text-dark small mb-0">Status: @if($item->allow_repeated_payments)<span class="text-muted">ALLOWED REPEATED PAYMENTS</span>@else<span class="text-muted">{{ strtoupper($item->status)}}</span>@endif</p>
                                        <p class="text-dark small mb-0">Created At: <span class="text-muted">{{ $item->created_at}}</span></p>
                                        @if($status != 'completed')
                                        <a href="{{route('dashboard.business.payment-links.delete', [$business, $item->id])}}" class="float-right">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                    @endforeach
                @else
                    <div class="card-body bg-light border-top p-4">
                        <div class="text-center text-muted py-4"><p class="small mb-0">- No payment link found -</p>
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
    </div>
    <div class="row">
        <div class="col-12 col-sm-12 col-md-12 col-lg-12">
            <business-help-guide :page_type="'payment_links'"></business-help-guide>
        </div>
    </div>
@endsection
@push('body-stack')
    <script type="text/javascript" defer>
        window.addEventListener('DOMContentLoaded', () => {
            $('.btn-copy').on('click', function(e) {
                let link = document.querySelector('#default_payment_link');
                link.setAttribute('type', 'text');
                link.select();

                try {
                    let copied = document.execCommand('copy');
                } catch (err) {
                    alert('Oops, unable to copy');
                }
                let input = document.querySelector('#input_default_link');
                input.focus();

                /* unselect the range */
                link.setAttribute('type', 'hidden')
                window.getSelection().removeAllRanges()
            });
        });

    </script>
@endpush
