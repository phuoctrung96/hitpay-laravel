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

        .invoice-box table tr td:nth-child(2) {
            text-align: right;
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

        .invoice-box table tr.total td:nth-child(2) {
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
                            <img src="{{asset('images/pdf-logo-sm.png')}}" style="width: 100%; max-width: 300px" />
                        </td>

                        <td>
                            <h1>Fee Invoice</h1>
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
                            HITPAY PAYMENT SOLUTIONS PTE LTD<br />
                            1 Keong Saik Road,<br />
                            Singapore,<br />
                            089109,<br />
                            support@hit-pay.com
                        </td>

                        <td>
                            Invoice Number: INV-{{$monthDate->format('Ym')}}-{{substr($business->getKey(), -7)}}<br />
                            Invoice Date: {{$monthDate->format('M d, Y')}}<br />
                            Service Month: {{$monthDate->format('M Y')}}<br />
                            HitPay Tax Number: 201605883W<br />
                            @if($business->tax_registration_number)Customer Tax Number {{$business->tax_registration_number}}<br />@endif
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
                            <b>Bill to {{$business->individual_name ?? $business->display_name}}</b><br />
                            @if($business->street){{$business->street}},<br /> @endif
                            @if($business->city){{$business->city}},<br />@endif
                            @if($business->postal_code){{$business->postal_code}},<br />@endif
                            @if($business->email){{$business->email}}@endif
                        </td>

                        <td></td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr class="heading">
            <td>Currency: {{strtoupper($business->currency)}}</td>

            <td></td>
        </tr>

        <tr class="details">
            <td>Transaction Volume</td>

            <td>${{number_format($total_volume,2)}}</td>
        </tr>
        <tr class="details">
            <td>HitPay Fees</td>

            <td>${{number_format($fee,2)}}</td>
        </tr>

        <tr class="total">
            <td></td>

            <td>Total Fees: ${{number_format(($fee), 2)}}</td>
        </tr>
    </table>
</div>
</body>
</html>
