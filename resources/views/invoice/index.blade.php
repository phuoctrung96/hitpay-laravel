@extends('layouts.root', [
'title' => 'Invoice',
])
@push('head-stack')
    <style>

    </style>
@endpush
<?php
$is_show_payment = false;
if (count($invoice->invoicePartialPaymentRequests) == 0) {
    $is_show_payment = true;
} else {
    if ($invoice->status != \App\Enumerations\Business\InvoiceStatus::PAID && !$invoice->isPartialityPaid())
        $is_show_payment = true;
}

if($invoice->status == App\Enumerations\Business\InvoiceStatus::PAID){
    $is_show_payment = false;
}
?>

<?php
// Check show discount
$is_show_discount = false;
foreach($invoice->products as $item){
    if(number_format($item['discount'] ?? 0) > 0) {
        $is_show_discount = true;
        break;
    }
}
?>
@section('root-content')
    <body class="view-invoice">
    <div id="app">
        <div class="inner-view-invoice">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-12 col-md-10 col-lg-9 col-xl-9 order-1 order-md-2">
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
                        <div class="card card-view-invoice shadow-sm">
                            <div class="card-body">
                                <div class="top-view-invoice">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="logo">
                                                @if($business->logo)
                                                    <img src="{{ $business->logo->getUrl()}}"
                                                         alt="{{ $business->getName() }}">
                                                @else
                                                    <img class="default" src="{{asset('images/logo-invoice.svg')}}"
                                                         alt=""/>
                                                @endif
                                            </div>
                                            <h2 class="mb-3">{{ $business->getName() }}</h2>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="date-invoice">
                                                <div class="row">
                                                    <div class="col-6">
                                                        <p class="title">
                                                            Invoice date:
                                                        </p>
                                                        <p class="date fw-500">
                                                            {{\Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y')}}
                                                        </p>
                                                    </div>
                                                    <div class="col-6">
                                                        <p class="title">
                                                            Due date:
                                                        </p>
                                                        <p class="date fw-500">
                                                            {{\Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y')}}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bill-invoice">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="bill bill-from">
                                                <p class="title">Bill from</p>
                                                <div class="information">
                                                    <p class="from fw-500">{{$business->individual_name ?? $business->display_name}}</p>
                                                    <p class="address is-gray">
                                                        @if($business->street || $business->street != '')
                                                            <span>{{$business->street}}, {{$business->city}}</span>
                                                        @else
                                                            <span>{{$business->city}}</span>
                                                        @endif
                                                        <span>{{$business->postal_code}}</span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="bill bill-to">
                                                <p class="title">Bill to</p>
                                                <div class="information">
                                                    <p class="from fw-500">{{$invoice->customer->name ?? ($invoice->customer->email ?? "")}}</p>
                                                    <p class="address is-gray">
                                                        @if($invoice->customer->street || $invoice->customer->street != '')
                                                            <span>{{$invoice->customer->street}}, {{$invoice->customer->city}}</span>
                                                        @else
                                                            <span>{{$invoice->customer->city}}</span>
                                                        @endif
                                                        <span>{{$invoice->customer->postal_code }}</span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="invoice-number">
                                    <div class="item">
                                        <p class="title">Invoice number:</p>
                                        <p class="fw-500">{{ $invoice->invoice_number }}</p>
                                    </div>

                                    @if($business->tax_registration_number)
                                        <div class="item">
                                            <p class="title">Business Tax Number:</p>
                                            <p class="fw-500">{{$business->tax_registration_number}}</p>
                                        </div>
                                    @endif
                                    @if($invoice->description)
                                        <div class="item">
                                            <p class="title">Description:</p>
                                            <p class="fw-500">{{$invoice->memo}}</p>
                                        </div>
                                    @endif
                                </div>
                                <div class="tbl-view-invoice d-block d-lg-none">
                                    <div class="top-title d-none d-lg-flex">
                                        <p class="d-lg-block title w-name">Item</p>
                                        <p class="d-lg-block title w-qty">Qty</p>
                                        <p class="d-lg-block title w-price">Price</p>
                                        @if($is_show_discount)
                                            <p class="d-lg-block title w-discount">
                                                Discount
                                            </p>
                                        @endif
                                        <p class="d-lg-block title w-amount">Amount</p>
                                    </div>
                                    @foreach($invoice->products as $item)
                                        <div class="row-product d-lg-flex fw-500">
                                            <p class="w-name d-flex justify-content-between align-items-center"><span
                                                    class="title d-lg-none">Item</span>{{$item['product']['name']}}</p>
                                            <p class="w-qty d-flex justify-content-between align-items-center"><span
                                                    class="title d-lg-none">Qty</span>{{$item['quantity']}}</p>
                                            <p class="w-price d-flex justify-content-between align-items-center"><span
                                                    class="title d-lg-none">Price</span>{{getReadableAmountByCurrency($invoice->currency, $item['variation']['price'])}} {{strtoupper($invoice->currency)}}
                                            </p>
                                            @if($is_show_discount == true)
                                                <p class="w-discount d-flex justify-content-between align-items-center">
                                                    <span class="title d-lg-none">Discount</span>{{number_format($item['discount'], 2) ?? ''}}
                                                </p>
                                            @endif
                                            <p class="w-amount d-flex justify-content-between align-items-center"><span
                                                    class="title d-lg-none">Amount</span>{{(getReadableAmountByCurrency($invoice->currency, $item['variation']['price']) * $item['quantity']) - ($item['discount'] ?? 0) }} {{strtoupper($invoice->currency)}}
                                            </p>
                                        </div>
                                    @endforeach
                                    @if($invoice->tax_setting)
                                        <div class="tax">
                                            <div
                                                class="tax-title d-none d-lg-flex justify-content-between align-items-center">
                                                <p class="title w-name">Tax</p>
                                                <p class="w-qty"></p>
                                                <p class="w-price"></p>
                                                @if($is_show_discount == true)
                                                    <p class="w-discount"></p>
                                                @endif
                                                <p class="title w-amount">Amount</p>
                                            </div>
                                            <div
                                                class="tax-amount justify-content-between align-items-center d-lg-flex fw-500">
                                                <p class="w-name d-flex justify-content-between align-items-center">
                                                    <span
                                                        class="title d-lg-none">Tax</span> {{$invoice->tax_setting->name}}
                                                    - {{$invoice->tax_setting->rate}}%</p>
                                                <p class="w-qty"></p>
                                                <p class="w-price"></p>
                                                @if($is_show_discount == true)
                                                    <p class="w-discount"></p>
                                                @endif
                                                @if($invoice->tax_setting)
                                                    <p class="w-amount d-flex justify-content-between align-items-center">
                                                        <span
                                                            class="title d-lg-none">Amount</span> {{ App\Helpers\Currency::getReadableAmount($invoice->amount_no_tax * $invoice->tax_setting->rate / 100, $invoice->currency)}}
                                                    </p>
                                                @else
                                                    <p class="w-amount d-flex justify-content-between align-items-center">
                                                        <span
                                                            class="title d-lg-none">Amount</span>{{App\Helpers\Currency::getReadableAmount($invoice->amount_no_tax, $invoice->currency)}}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                    <div class="total d-flex justify-content-between align-items-center">
                                        <p class="title w-name">Total</p>
                                        <p class="w-qty"></p>
                                        <p class="w-price"></p>
                                        @if($is_show_discount == true)
                                            <p class="w-discount"></p>
                                        @endif
                                        <p class="w-amount">{{App\Helpers\Currency::getReadableAmount($invoice->amount, $invoice->currency)}}</p>
                                    </div>
                                </div>
                                <div class="tbl-view-invoice-desktop d-none d-lg-block">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th class="title w-name">Item</th>
                                                <th class="title w-qty">Qty</th>
                                                <th class="title w-price">Price</th>
                                                @if($is_show_discount == true)
                                                    <th class="title w-discount">
                                                        Discount
                                                    </th>
                                                @endif
                                                <th class="title w-amount">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($invoice->products as $item)
                                                <tr class="row-product fw-500">
                                                    <td class="w-name">
                                                        {{$item['product']['name']}}
                                                    </td>
                                                    <td class="w-qty text-nowrap">
                                                        {{$item['quantity']}}
                                                    </td>
                                                    <td class="w-price text-nowrap">
                                                        {{getReadableAmountByCurrency($invoice->currency, App\Enumerations\CurrencyCode::isNormal($invoice->currency) ? $item['variation']['price'] : $item['variation']['price'] /100)}} {{strtoupper($invoice->currency)}}
                                                    </td>
                                                    @if($is_show_discount == true)
                                                        <td class="w-discount text-nowrap">
                                                            {{number_format($item['discount'], 2) ?? ''}}
                                                        </td>
                                                    @endif
                                                    <td class="w-amount text-nowrap">
                                                        {{(getReadableAmountByCurrency($invoice->currency, App\Enumerations\CurrencyCode::isNormal($invoice->currency) ? $item['variation']['price'] : $item['variation']['price'] /100) * $item['quantity']) - ($item['discount'] ?? 0) }} {{strtoupper($invoice->currency)}}
                                                    </td>
                                                </tr>
                                            @endforeach
                                            @if($invoice->tax_setting)
                                                <tr class="tax">
                                                    <td class="title w-name">Tax</td>
                                                    <td></td>
                                                    <td></td>
                                                    @if($is_show_discount == true)
                                                        <td></td>
                                                    @endif
                                                    <td class="title w-amount text-nowrap">Amount</td>
                                                </tr>
                                                <tr class="tax-amount fw-500">
                                                    <td class="w-name">
                                                        {{$invoice->tax_setting->name}}
                                                        - {{$invoice->tax_setting->rate}}%
                                                    </td>
                                                    <td></td>
                                                    <td></td>
                                                    @if($is_show_discount == true)
                                                        <td></td>
                                                    @endif
                                                    @if($invoice->tax_setting)
                                                        <td class="w-amount text-nowrap">
                                                            {{ App\Helpers\Currency::getReadableAmount($invoice->amount_no_tax * ($invoice->tax_setting->rate / 100), $invoice->currency)}}
                                                        </td>
                                                    @else
                                                        <td class="w-amount text-nowrap">{{App\Helpers\Currency::getReadableAmount($invoice->amount_no_tax, $invoice->currency)}}
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endif
                                            <tr class="total">
                                                <td class="title w-name">Total</td>
                                                <td></td>
                                                <td></td>
                                                @if($is_show_discount == true)
                                                    <td></td>
                                                @endif
                                                <td class="w-amount text-nowrap">
                                                    {{strtoupper($invoice->currency)}} {{App\Helpers\Currency::getReadableAmount($invoice->amount, $invoice->currency)}}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="invoice-description">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="reference">
                                                <p class="title">Reference</p>
                                                <div class="is-text fw-500">
                                                    <p>{{$invoice->reference}}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="description">
                                                <p class="title">Description</p>
                                                <div class="is-text fw-500">
                                                    <p>{{$invoice->memo}}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="payment">
                                    <div class="row">
                                        @if(count($invoice->invoicePartialPaymentRequests) == 0)
                                            <div class="col-lg-6">
                                                <p class="title">Payments</p>
                                            </div>
                                        @else
                                            <div class="col-lg-6">
                                                <p class="title">Partial payments</p>
                                                <div class="information">
                                                    <p class="fw-500">{{(count($invoice->invoicePartialPaymentRequests) > 0) ? 'Allowed' : ''}}</p>
                                                </div>
                                            </div>
                                        @endif
                                        <div class="col-lg-6">
                                                @foreach($invoice->invoicePartialPaymentRequests as $i => $partialRequest)
                                                    @php($charge = $partialRequest->paymentRequest->getPayments()->first())
                                                    @if(!$charge || ($charge && $charge->status != \App\Enumerations\Business\ChargeStatus::SUCCEEDED))
                                                        <div
                                                            class="row payment-item justify-content-between align-items-center">
                                                            <div class="col-6">
                                                                <label for="partialPayment{{$i}}"
                                                                    class="label-checkbox">Pay Payment {{$i + 1}}
                                                                    <input type="radio" name="partialPayment" checked
                                                                        onclick="handleClick(this);"
                                                                        id="partialPayment{{$i}}" value="{{$i}}"
                                                                        amount="{{$partialRequest->amount}}">
                                                                    <span class="checkmark"></span>
                                                                </label>
                                                            </div>
                                                            <div class="col-6">
                                                                <span class="is-gray">
                                                                    Due date {{\Carbon\Carbon::parse($invoice->invoicePartialPaymentRequests[$i]['due_date'])->format('d/m/Y') }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                        @break
                                                    @else
                                                        @php($is_show_payment = false)
                                                    @endif
                                                @endforeach
                                            @if($is_show_payment)
                                                @if(count($invoice->invoicePartialPaymentRequests) == 0)
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <label for="fullPayment" class="label-checkbox">
                                                                Pay Full Payment
                                                                <input type="radio" name="partialPayment" checked
                                                                       onclick="handleClick(this);"
                                                                       id="fullPayment" value="full"
                                                                       amount="{{App\Helpers\Currency::getReadableAmount($invoice->amount, $invoice->currency)}}">
                                                                <span class="checkmark"></span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <label for="fullPayment" class="label-checkbox">
                                                                Pay Full Payment
                                                                <input type="radio" name="partialPayment"
                                                                       onclick="handleClick(this);"
                                                                       id="fullPayment" value="full"
                                                                       amount="{{App\Helpers\Currency::getReadableAmount($invoice->amount, $invoice->currency)}}">
                                                                <span class="checkmark"></span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex btn-view-invoice justify-content-between">
                                    <a href="{{route('invoice.download', [$business->id, $invoice->id])}}"
                                       class="btn btn-download">
                                        <img src="{{asset('images/ico-download.svg')}}"/>Download PDF
                                    </a>
                                    @if($invoice->status != \App\Enumerations\Business\InvoiceStatus::PAID)
                                        <button onclick="pay()" class="btn btn-pay btn-primary" id="pay">Pay</button>
                                    @else
                                        <p>The invoice has been paid successfully</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer-view-invoice">
                <div class="container">
                    <div class="row d-lg-flex justify-content-center">
                        <div class="col-12 col-md-10 col-lg-9 col-xl-9 order-1 order-md-2">
                            <div class="d-lg-flex justify-content-between">
                                @if($business->logo)
                                    <div class="logo">
                                        <img src="{{ $business->logo->getUrl()}}" alt="{{ $business->getName() }}">
                                    </div>
                                @endif
                                @if($business->email)
                                    <div class="contact">
                                        <p>Contact us</p>
                                        <p>Email: {{ $business->email}}</p>
                                        <p>Phone number: {{ $business->phone_number}}</p>
                                    </div>
                                @endif
                                @if(($business->logo == null || $business->logo == '') && ($business->email == null || $business->email == ''))
                                    <div class="copyright is-center">
                                        <p class="powered fw-500">Powered by HitPay</p>
                                        <p class="is-copyright">©2021 HITPAY PAYMENT SOLUTIONS PTE LTD</p>
                                    </div>
                                @else
                                    <div class="copyright">
                                        <p class="powered fw-500">Powered by HitPay</p>
                                        <p class="is-copyright">©2021 HITPAY PAYMENT SOLUTIONS PTE LTD</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </body>
@endsection
<script>
    function pay() {
        let partialPayments = @json($invoice->invoicePartialPaymentRequests);

        let index = $('input[name="partialPayment"]:checked').val();
        if (index === 'full')
            var url = "{{$invoice->paymentRequest->url}}";
        else
            var url = partialPayments[index].payment_request.url;

        document.location.href = url;

    }

    document.addEventListener("DOMContentLoaded", function (event) {
        let amount = document.querySelector('input[name="partialPayment"]:checked').getAttribute('amount');
        let currency = @json($invoice->currency);
        if (amount != undefined) {
            document.querySelector('#pay').innerHTML = 'Pay ' + currency.toUpperCase() + ' ' + amount;
        }
    });

    function handleClick(myRadio) {
        let amount = myRadio.getAttribute('amount');
        let currency = @json($invoice->currency);
        if (amount != undefined) {
            document.querySelector('#pay').innerHTML = 'Pay ' + currency.toUpperCase() + ' ' + amount;
        }
    }

</script>
