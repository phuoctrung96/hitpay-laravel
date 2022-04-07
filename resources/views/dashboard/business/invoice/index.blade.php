@extends('layouts.business', [
    'title' => 'Invoices'
])

@section('business-content')
    @if(session('success_message'))
        <div
            class="alert alert-success border-left-0 border-right-0 rounded-0 alert-dismissible fade show"
            role="alert">
            {{ session('success_message') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    @if(session('invoice.deleted'))
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="alert alert-success" role="alert">
                    The invoice has been successful deleted!
                </div>
            </div>
        </div>
    @endif
    @if(session('invoice.resend'))
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="alert alert-success" role="alert">
                    The invoice has been successful resend!
                </div>
            </div>
        </div>
    @endif
    @if(session('invoice.remind'))
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="alert alert-success" role="alert">
                    The invoice has been successful reminded!
                </div>
            </div>
        </div>
    @endif

    <div class="row copied-message justify-content-center">
        <div class="col-lg-8">
            <div class="alert alert-success" role="alert">
                The payment link has been copied!
            </div>
        </div>
    </div>
    <div class="invoice-list">
        <div class="card card-meta-invoice shadow-sm border-0 shadow-sm mb-3">
            <div class="meta-invoice-report">
                <div class="ctn-report d-flex justify-content-between align-items-center">
                    <div class="reports d-flex align-items-center">
                        <div class="month-report item-report">
                            <p class="is-title">Invoices paid this month</p>
                            <div class="amount">
                                <p class="mb-0">
                                    <span class="total">{{$invoiceMonth}}</span>
                                </p>
                            </div>
                        </div>
                        <div class="pending-report item-report">
                            <p class="is-title">Pending invoices</p>
                            <div class="amount">
                                <p class="mb-0">
                                    <span class="total">{{$pendingAmount}}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="btn-group d-flex align-items-center">
                        <div class="add-invoice">
                            <a href="{{ route('dashboard.business.invoice.bulk', $business->getKey()) }}" class="btn btn-outline-primary" data-toggle="button" aria-pressed="false" autocomplete="off">
                             <span>Add invoices in bulk</span>
                            </a>
                        </div>
                        <!-- <div class="setting">
                            <button type="button" class="btn btn-outline-secondary ml-3" data-toggle="button" aria-pressed="false" autocomplete="off">
                                <span>Settings</span>
                            </button>
                        </div> -->
                    </div>
                </div>
            </div>
        </div>
        <div class="card card-main-invoice shadow-sm border-0 shadow-sm mb-3">
            <div class="main-invoice-list">
                <div class="top-invoice-list d-flex justify-content-between align-items-center">
                    <div class="filter">
                        <div class="btn-group">
                          <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Filter: <span id="filter_name">{{$status??'All'}}</span>
                          </button>
                          <div class="dropdown-menu border-0 shadow-sm" id="ddlFilter">
                            <a class="dropdown-item" href="{{ route('dashboard.business.invoice.index', [$business->getKey(),
                                                                                                        'status' => \App\Enumerations\Business\InvoiceStatus::ALL]) }}">All</a>
                            <a class="dropdown-item" href="{{ route('dashboard.business.invoice.index', [$business->getKey(),'status' => \App\Enumerations\Business\InvoiceStatus::SENT]) }}">Sent</a>
                            <a class="dropdown-item" href="{{ route('dashboard.business.invoice.index', [$business->getKey(),'status' => \App\Enumerations\Business\InvoiceStatus::OVERDUE]) }}">Overdue</a>
                            <a class="dropdown-item" href="{{ route('dashboard.business.invoice.index', [$business->getKey(),'status' => \App\Enumerations\Business\InvoiceStatus::PAID]) }}">Paid</a>
                          </div>
                        </div>
                    </div>
                    <div class="search">
                    <form class="input-group input-group-lg" action="{{ route('dashboard.business.invoice.index', [
                    'business_id' => $business->getKey(),
                ]) }}">
                    <input class="form-control" placeholder="Search invoices"
                           title="Search Charge" name="keywords" value="{{ $keywords = request('keywords') }}">
                    <div class="input-group-append">
                        <button class="btn">Search</button>
                    </div>
                </form>
                    </div>
                    <div class="create-invoice">
                        <a href="{{ route('dashboard.business.invoice.create', $business->id) }}" class="btn btn-primary" data-toggle="button" aria-pressed="false" autocomplete="off">
                          Create invoice
                        </a>
                    </div>
                </div>
                <div class="tbl-invoice-list">
                    @if ($paginator->count())
                        <div class="is-table">
                            <table>
                                <thead>
                                    <tr>
                                        <td>Invoice number</td>
                                        <td>Reference</td>
                                        <td>Customer</td>
                                        <td>Date</td>
                                        <td>Amount</td>
                                        <td>Status</td>
                                        <td>&nbsp;</td>
                                    </tr>
                                </thead>
                                <tbody>
                                        @foreach ($paginator as $item)
                                        <tr>
                                            <td class="invoice-number" onclick="clickDetail('{{ route('dashboard.business.invoice.detail',[$business->id,$item->getKey()]) }}')">{{ $item->invoice_number?? '' }}</a></td>
                                            <td class="reference" onclick="clickDetail('{{ route('dashboard.business.invoice.detail',[$business->id,$item->getKey()]) }}')">{{ $item->reference ?? '' }}</td>
                                            <td onclick="clickDetail('{{ route('dashboard.business.invoice.detail',[$business->id,$item->getKey()]) }}')">{{ $item->customer->name ?? ($item->customer->email ?? '') }}</td>
                                            <td onclick="clickDetail('{{ route('dashboard.business.invoice.detail',[$business->id,$item->getKey()]) }}')">{{ $item->created_at->format('d.m.Y') }}</td>
                                            <td onclick="clickDetail('{{ route('dashboard.business.invoice.detail',[$business->id,$item->getKey()]) }}')">{{strtoupper($item->currency)}} {{ App\Helpers\Currency::getReadableAmount($item->amount, $item->currency) }}</td>
                                            <td onclick="clickDetail('{{ route('dashboard.business.invoice.detail',[$business->id,$item->getKey()]) }}')">
                                                @switch ($item->getCustomStatus())
                                                        @case (\App\Enumerations\Business\InvoiceStatus::PAID)
                                                        <p class="status success">Paid</p>
                                                        @break
                                                        @case (\App\Enumerations\Business\InvoiceStatus::PARTIALITY_PAID)
                                                        <p class="status partiality">Partially paid</p>
                                                        @break
                                                        @case (\App\Enumerations\Business\InvoiceStatus::PENDING)
                                                        <p class="status sent">Sent</p>
                                                        @break
                                                        @case (\App\Enumerations\Business\InvoiceStatus::SENT)
                                                        <p class="status sent">Sent</p>
                                                        @break
                                                        @case (\App\Enumerations\Business\InvoiceStatus::OVERDUE)
                                                        <p class="status overdue">Overdue</p>
                                                        @break
                                                        @default
                                                        <p class="status partiality">{{ $item->status }}</p>
                                                    @endswitch
                                            </td>
                                            <td id="action">
                                                <div class="action" id="action">
                                                    <div class="icon" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" id="action">
                                                        <img src="/images/ico-action.svg" alt="">
                                                    </div>
                                                    <div class="dropdown-menu dropdown-menu-right">
                                                                @if($item->status != \App\Enumerations\Business\InvoiceStatus::DRAFT)
                                                                    <button class="dropdown-item text"
                                                                            onclick="invoice.copy('{{route('invoice.hosted.show', [$business->id, $item->getKey()])}}')">
                                                                        Copy invoice link
                                                                    </button>
                                                                @endif
                                                                @if($item->status != \App\Enumerations\Business\InvoiceStatus::PAID && $item->isOverdue() && !$item->isPartialityPaid())
                                                                    <a class="dropdown-item text" href="{{ route(
                                                                                        'dashboard.business.invoice.edit',
                                                                                        [
                                                                                            $business->id,
                                                                                            $item->getKey()
                                                                                            ]
                                                                                        ) }}">
                                                                        Edit Invoice
                                                                    </a>
                                                                @endif
                                                                @if($item->status != \App\Enumerations\Business\InvoiceStatus::PAID && $item->status != \App\Enumerations\Business\InvoiceStatus::DRAFT && !$item->isOverdue())
                                                                    <form
                                                                        action="{{ route(
                                                                                        'dashboard.business.invoice.resend',
                                                                                        [
                                                                                            $business->id,
                                                                                            $item->getKey()
                                                                                            ]
                                                                                        ) }}"
                                                                        method="post">
                                                                        @csrf
                                                                        <button class="dropdown-item text">Resend Invoice</button>
                                                                    </form>
                                                                @endif
                                                                @if($item->isOverdue())
                                                                    <form
                                                                        action="{{ route(
                                                                                        'dashboard.business.invoice.send.remind',
                                                                                        [
                                                                                            $business->id,
                                                                                            $item->getKey()
                                                                                            ]
                                                                                        ) }}"
                                                                        method="post">
                                                                        @csrf
                                                                        <button class="dropdown-item text">Send Reminder</button>
                                                                    </form>
                                                                @endif
                                                                @if($item->status == \App\Enumerations\Business\InvoiceStatus::DRAFT)
                                                                    <form
                                                                        action="{{ route('dashboard.business.invoice.save.undraft',
                                                                            [
                                                                            $business->id,
                                                                            $item->getKey()
                                                                            ]) }}"
                                                                        method="post">
                                                                        @csrf
                                                                        <button class="dropdown-item text">Save and Send</button>
                                                                    </form>
                                                                @endif
                                                                <form
                                                                    action="{{ route(
                                                                                        'dashboard.business.invoice.print',
                                                                                        [
                                                                                            $business->id,
                                                                                            $item->getKey()
                                                                                            ]
                                                                                        ) }}"
                                                                    method="post">
                                                                    @csrf
                                                                    <button class="dropdown-item text">Download PDF</button>
                                                                </form>
                                                                @if($item->status != \App\Enumerations\Business\InvoiceStatus::PAID)
                                                                <a class="dropdown-item text red" href="#" data-toggle="modal" data-target="#deleteInvoiceModal-{{$item->getKey()}}">Delete Invoice</a>
                                                                    <!-- <form
                                                                        action="{{ route(
                                                                                        'dashboard.business.invoice.delete',
                                                                                        [
                                                                                            $business->id,
                                                                                            $item->getKey()
                                                                                            ]
                                                                                        ) }}"
                                                                        method="post">
                                                                        @csrf
                                                                        <button class="dropdown-item text red">Delete Invoice
                                                                        </button>
                                                                    </form> -->
                                                                    
                                                                @endif
                                                            </div>
                                                </div>
                                                <div class="modal fade modal-delete-invoice" id="deleteInvoiceModal-{{$item->getKey()}}" tabindex="-1" role="dialog" aria-labelledby="deleteInvoiceModal" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title font-weight-bold text-danger" id="regenerateModalLabel">Warning!</h5>
                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                    <img src="/images/delete_icon.svg" alt="delete" class="btn-delete-invoice">
                                                                </button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p id="warning-text" class="mb-2 mt-2">Are you sure you want to delete this invoice?</p>
                                                                
                                                            </div>
                                                            <div class="modal-footer">
                                                            <form method="post" action="{{ route('dashboard.business.invoice.delete',
                                                                    [
                                                                        $business->id,
                                                                        $item->getKey()
                                                                        ]
                                                                    ) }}">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                                                </form>
                                                                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" aria-label="Close">
                                                                    Cancel
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="card-body border-top p-4">
                            <div class="text-center text-muted pt-4">
                                <p><i class="fa fas fa-sync fa-4x"></i></p>
                                <p class="small mb-0">- No invoices found -</p>
                            </div>
                        </div>
                    @endif
                </div>

                @if ($paginator->lastPage() > 1)
                <div class="pagination-invoice">
                    <ul class="pagination">
                        <li class="page-item first{{ ($paginator->currentPage() == 1) ? ' disabled' : '' }}" >
                            <a class="page-link" href="{{ $paginator->url(1) }}">Prev</a>
                        </li>
                        @for ($i = 1; $i <= $paginator->lastPage(); $i++)
                            <li class="page-item page-number {{ ($paginator->currentPage() == $i) ? ' active' : '' }}">
                                <a class="page-link" href="{{ $paginator->url($i) }}">{{ $i }}</a>
                            </li>
                        @endfor
                        <li class="page-item last{{ ($paginator->currentPage() == $paginator->lastPage()) ? ' disabled' : '' }}">
                            <a class="page-link" href="{{ $paginator->url($paginator->currentPage()+1) }}" >Next</a>
                        </li>
                    </ul>
                </div>
                @endif
            </div>
        </div>
    </div>
    <business-help-guide :page_type="'invoicing'"></business-help-guide>
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

        function clickDetail(url) {
            window.location.href = url;
        }
    </script>
@endpush
