<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />

    <style>
        .invoice-box {
            font-size: 16px;
            line-height: 24px;
            font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
            color: #555;
        }

        .invoice-box table {
            width: 100%;
            line-height: inherit;
            text-align: left;
        }

        .invoice-box table td {
            padding: 5px;
            vertical-align: top;
        }

        .invoice-box table tr td:nth-child(3) {
            text-align: right;
        }

        .invoice-box table tr td:nth-child(4) {
            text-align: right;
        }

        .invoice-box table tr td:nth-child(2) {
            text-align: right;
        }

        .total-table tr td:nth-child(2){
            width: 25%;
        }

        .total-table tr td:nth-child(3){
            width: 25%;
        }
        .total-table tr td:nth-child(4){
            width: 15%;
        }

        .total-table{
            font-size: 14px;
        }

        .invoice-box table tr.top table td {
            padding-bottom: 20px;
        }

        .invoice-box table tr.top table td.title {
            font-size: 45px;
            line-height: 45px;
            color: #333;
        }

        .invoice-box table tr.information table td {
            padding-bottom: 40px;
        }

        .invoice-box table tr.heading td {
            background: #eee;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }

        .invoice-box table tr.details td {
            padding-bottom: 20px;
        }

        .invoice-box table tr.item td {
            border-bottom: 1px solid #eee;
        }

        .invoice-box table tr.item.last td {
            border-bottom: none;
        }

        .invoice-box table tr.total td:nth-child(3) {
            border-top: 2px solid #eee;
            font-weight: bold;
        }

        .invoice-box table tr.total td:nth-child(4) {
            border-top: 2px solid #eee;
            font-weight: bold;
        }

        @media only screen and (max-width: 600px) {
            .invoice-box table tr.top table td {
                width: 100%;
                display: block;
                text-align: center;
            }

            .invoice-box table tr.information table td {
                width: 100%;
                display: block;
                text-align: center;
            }
        }

        /** RTL **/
        .invoice-box.rtl {
            direction: rtl;
            font-family: Tahoma, 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
        }

        .invoice-box.rtl table {
            text-align: right;
        }

        .invoice-box.rtl table tr td:nth-child(2) {
            text-align: left;
        }
    </style>
</head>

<body>
<div class="invoice-box">
    <table cellpadding="0" cellspacing="0">
        <tr class="top">
            <td colspan="2">
                <table>
                    <tr>
                        <td class="title">
                            <img src="{{$business->logo ? $business->logo->getUrl() : asset('images/pdf-logo.png')}}" style="width: 100%; max-width: 100px" />
                        </td>

                        <td>
                            <h1>Invoice {{$invoice->invoice_number}}</h1>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr class="information">
            <td colspan="2">
                <table>
                    <tr>
                        <td>
                            {{$business->individual_name ?? $business->display_name}}</b><br />
                            {{$business->street}},<br />
                            {{$business->city}},<br />
                            {{$business->postal_code}},<br />
                            {{$business->email}}
                        </td>

                        <td>
                            Invoice ID: {{$invoice->id}}<br/>
                            Invoice Reference: {{$invoice->reference}}<br />
                            Invoice Date: {{\Carbon\Carbon::parse($invoice->invoice_date)->format('M d Y')}}<br />
                            Due Date: {{\Carbon\Carbon::parse($invoice->due_date)->format('M d Y')}}<br />
                            Status: @if($invoice->isOverdue())
                                <span class="text-warning font-weight-bold small mb-2">Overdue</span>
                            @else
                                @switch ($invoice->status)
                                    @case (\App\Enumerations\Business\InvoiceStatus::PAID)
                                    <span class="text-success font-weight-bold small mb-2">Paid</span>
                                    @break
                                    @case (\App\Enumerations\Business\InvoiceStatus::PENDING)
                                    <span class="text-warning font-weight-bold small mb-2">Pending</span>
                                    @break
                                    @case (\App\Enumerations\Business\InvoiceStatus::SENT)
                                    <span class="text-info font-weight-bold small mb-2">Sent</span>
                                    @break
                                    @default
                                    <span class="badge badge-secondary">{{ $invoice->status }}</span>
                                @endswitch
                            @endif
                            <br/>
                            @if($business->tax_registration_number)Business Tax Number: {{$business->tax_registration_number}}<br />@endif
                            @if($invoice->description)Description: {{$invoice->memo}}@endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr class="information">
            <td colspan="2">
                <table>
                    <tr>
                        <td>
                            <b>Bill to {{$invoice->customer->name}}</b><br />
                            {{$invoice->customer->email}}
                        </td>

                        <td></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <table cellpadding="0" cellspacing="0" class="total-table">
        <tr class="heading">
            <td>Currency: {{strtoupper($invoice->currency)}}</td>
            <td>Discount</td>
            <td>Amount</td>
            @if($invoice->tax_setting)<td >{{$invoice->tax_setting->name}}({{round($invoice->tax_setting->rate,2)}}%)</td>@endif
        </tr>
        @foreach($invoice->products as $item)
            <tr>
                <td>{{$item['product']['name']}} {{$item['variation']['description'] ? $item['variation']['description'] : ''}} (Quantity: {{$item['quantity']}})</td>
                <td>{{number_format((int) $item['discount'], 2)}}</td>
                <td>{{App\Helpers\Currency::getReadableAmount($item['product']['price'] * $item['quantity'], $invoice->currency)}}</td>
                @if($invoice->tax_setting)<td>{{App\Helpers\Currency::getReadableAmount($item['product']['price'] * $item['quantity'] * $invoice->tax_setting->rate / 100, $invoice->currency)}}</td>@endif
            </tr>
        @endforeach

        <tr class="total">
            <td></td>
            <td></td>
            <td>{{App\Helpers\Currency::getReadableAmount($invoice->amount_no_tax, $invoice->currency)}}</td>
            @if($invoice->tax_setting)<td >{{ App\Helpers\Currency::getReadableAmount($invoice->amount_no_tax * $invoice->tax_setting->rate / 100, $invoice->currency)}}</td>@endif
        </tr>

        <tr class="total">
            <td></td>
            <td></td>
            @if($invoice->tax_setting)<td></td>@endif
            <td>Total: {{App\Helpers\Currency::getReadableAmount($invoice->amount, $invoice->currency)}}</td>
        </tr>
        <tr>
            <td>
                Payment Link: {{route('invoice.hosted.show', [$invoice->business->id, $invoice->id])}}
            </td>
        </tr>
    </table>
</div>
</body>
</html>
