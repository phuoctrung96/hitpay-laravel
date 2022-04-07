@extends('layouts.business', [
    'title' => 'Invoice - ' . $invoice->invoice_number??'',
])

@section('business-content')
    <div class="row">
        <div class="col-md-9 col-lg-9 mb-4">
            <div class="g-back-meta">
                <a class="btn-back" href="{{ route('dashboard.business.invoice.index', [
                    'business_id' => $business->getKey(),
                ]) }}">
                    <img src="{{asset('images/ico-back-normal.svg')}}"/>Back to Invoices
                </a>
            </div>
        </div>
    </div>
    <div class="row copied-message">
        <div class="col-lg-8">
            <div class="alert alert-success" role="alert">
                The payment link has been copied!
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-9 col-lg-9 main-content">
            <div class="invoice-created">
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        {{--                    <div class="float-right">--}}
                        {{--                        <div class="edit">--}}
                        {{--                            <div class="btn-group">--}}
                        {{--                                <div class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true"--}}
                        {{--                                     aria-expanded="false">--}}
                        {{--                                                    <span class="edit-button">--}}
                        {{--                                                    </span>--}}
                        {{--                                </div>--}}
                        {{--                                <div class="dropdown-menu dropdown-menu-right">--}}
                        {{--                                    @if($invoice->status != \App\Enumerations\Business\InvoiceStatus::DRAFT)--}}
                        {{--                                        <button class="dropdown-item text"--}}
                        {{--                                                onclick="invoice.copy('{{$invoice->paymentRequest->url}}')">--}}
                        {{--                                            Copy Payment Link--}}
                        {{--                                        </button>--}}
                        {{--                                    @endif--}}
                        {{--                                    @if($invoice->status != \App\Enumerations\Business\InvoiceStatus::PAID || $invoice->isOverdue())--}}
                        {{--                                        <a class="dropdown-item text" href="{{ route(--}}
                        {{--                                                                            'dashboard.business.invoice.edit',--}}
                        {{--                                                                            [--}}
                        {{--                                                                                $business->id,--}}
                        {{--                                                                                $invoice->getKey()--}}
                        {{--                                                                                ]--}}
                        {{--                                                                            ) }}">--}}
                        {{--                                            Edit Invoice--}}
                        {{--                                        </a>--}}
                        {{--                                    @endif--}}
                        {{--                                    @if($invoice->status != \App\Enumerations\Business\InvoiceStatus::PAID && $invoice->status != \App\Enumerations\Business\InvoiceStatus::DRAFT && !$invoice->isOverdue())--}}
                        {{--                                        <form--}}
                        {{--                                            action="{{ route(--}}
                        {{--                                                                            'dashboard.business.invoice.resend',--}}
                        {{--                                                                            [--}}
                        {{--                                                                                $business->id,--}}
                        {{--                                                                                $invoice->getKey()--}}
                        {{--                                                                                ]--}}
                        {{--                                                                            ) }}"--}}
                        {{--                                            method="post">--}}
                        {{--                                            @csrf--}}
                        {{--                                            <button class="dropdown-item text">Resend Invoice</button>--}}
                        {{--                                        </form>--}}
                        {{--                                    @endif--}}
                        {{--                                    @if($invoice->isOverdue())--}}
                        {{--                                        <form--}}
                        {{--                                            action="{{ route(--}}
                        {{--                                                                            'dashboard.business.invoice.send.remind',--}}
                        {{--                                                                            [--}}
                        {{--                                                                                $business->id,--}}
                        {{--                                                                                $invoice->getKey()--}}
                        {{--                                                                                ]--}}
                        {{--                                                                            ) }}"--}}
                        {{--                                            method="post">--}}
                        {{--                                            @csrf--}}
                        {{--                                            <button class="dropdown-item text">Send Reminder</button>--}}
                        {{--                                        </form>--}}
                        {{--                                    @endif--}}
                        {{--                                    @if($invoice->status == \App\Enumerations\Business\InvoiceStatus::DRAFT)--}}
                        {{--                                        <form--}}
                        {{--                                            action="{{ route('dashboard.business.invoice.save.undraft',--}}
                        {{--                                                                 [--}}
                        {{--                                                                  $business->id,--}}
                        {{--                                                                  $invoice->getKey()--}}
                        {{--                                                                 ]) }}"--}}
                        {{--                                            method="post">--}}
                        {{--                                            @csrf--}}
                        {{--                                            <button class="dropdown-item text">Save and Send</button>--}}
                        {{--                                        </form>--}}
                        {{--                                    @endif--}}
                        {{--                                    <form--}}
                        {{--                                        action="{{ route(--}}
                        {{--                                                          'dashboard.business.invoice.print',--}}
                        {{--                                                          [--}}
                        {{--                                                             $business->id,--}}
                        {{--                                                             $invoice->getKey()--}}
                        {{--                                                          ]) }}"--}}
                        {{--                                        method="post">--}}
                        {{--                                        @csrf--}}
                        {{--                                        <button class="dropdown-item text">Print PDF</button>--}}
                        {{--                                    </form>--}}
                        {{--                                    @if($invoice->status != \App\Enumerations\Business\InvoiceStatus::PAID)--}}
                        {{--                                        <form--}}
                        {{--                                            action="{{ route(--}}
                        {{--                                                                            'dashboard.business.invoice.delete',--}}
                        {{--                                                                            [--}}
                        {{--                                                                                $business->id,--}}
                        {{--                                                                                $invoice->getKey()--}}
                        {{--                                                                                ]--}}
                        {{--                                                                            ) }}"--}}
                        {{--                                            method="post">--}}
                        {{--                                            @csrf--}}
                        {{--                                            <button class="dropdown-item text red">Delete Invoice</button>--}}
                        {{--                                        </form>--}}
                        {{--                                    @endif--}}
                        {{--                                </div>--}}
                        {{--                            </div>--}}
                        {{--                        </div>--}}
                        {{--                    </div>--}}
                        <p class="text-inform">Invoice has been successfully
                            created @if($invoice->status === \App\Enumerations\Business\InvoiceStatus::SENT)
                                and sent
                                to <a href="mailto:{{$invoice->customer->email}}">{{$invoice->customer->email}}@endif</a></p>
                        <p class="invoice-number">
                            <img src="{{asset('images/ico-success.svg')}}"/> {{ $invoice->created_at->format('F') . ' invoice '. $invoice->invoice_number }}
                        </p>

                        <p class="price">
                            {{strtoupper($invoice->currency)}} {{ App\Helpers\Currency::getReadableAmount($invoice->amount, $invoice->currency) }}
                        </p>
                        <div class="d-flex link-item justify-content-between align-items-center">
                            <p class="text-muted mt-2 mb-2">
                                <a target="_blank"
                                      href="{{route('invoice.hosted.show', [$invoice->business->id, $invoice->id])}}">{{ substr(route('invoice.hosted.show', [$invoice->business->id, $invoice->id]),0,60)}}
                                        ...</a>
                            </p>
                            <div class="copy">
                                    <span 
                                        style="cursor: pointer"
                                        onclick="invoice.copy('{{route('invoice.hosted.show', [$invoice->business->id, $invoice->id])}}')">
                                        <img src="{{asset('images/ico-copy.svg')}}"/> Copy invoice link
                                    </span>
                            </div>
                        </div>
                        <div class="d-flex d-xs-block btn-group-success justify-content-between mt-3">
                            <form action="{{ route('dashboard.business.invoice.print',[$business->id,$invoice->getKey()]) }}"
                                method="post">
                                @csrf
                                <button class="btn download btn-secondary">
                                    <img src="{{asset('images/ico-download.svg')}}"/>
                                    Download PDF
                                </button>
                            </form>
                            <a href="{{ route('dashboard.business.invoice.index', [
                                'business_id' => $business->getKey(),
                            ]) }}" class="btn btn-primary px-5">Done</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('body-stack')
    <script type="application/javascript">
        var invoice = {
            copy: function (text) {
                if (window.clipboardData && window.clipboardData.setData) {
                    clipboardData.setData("Text", text);
                } else if (document.queryCommandSupported && document.queryCommandSupported("copy")) {
                    var textarea = document.createElement("textarea");
                    textarea.textContent = text;
                    textarea.style.position = "fixed";
                    document.body.appendChild(textarea);
                    textarea.select();
                    try {
                        document.execCommand("copy");
                    } catch (ex) {
                        console.warn("Copy to clipboard failed.", ex);
                        return false;
                    } finally {
                        document.body.removeChild(textarea);
                    }
                }

                var copiedMessage = document.querySelector('.copied-message');
                if (copiedMessage) {
                    copiedMessage.style.display = 'block';
                    setTimeout(function () {
                        copiedMessage.style.display = 'none';
                    }, 2000)
                }
            }
        }
    </script>
@endpush
