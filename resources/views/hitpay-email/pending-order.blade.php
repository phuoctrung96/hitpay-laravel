@extends('hitpay-email.layouts.base', [
    'title' => 'You have pending shipment orders',
    'preheader' => 'List of Pending orders yet to be shipped',
])

@section('content')
    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">
        <h1 style="color: #222222; font-family: sans-serif; font-weight: 300; line-height: 1.4; margin: 0; Margin-bottom: 30px; font-size: 25px; text-align: center; text-transform: capitalize;">
            {{ 'List of Pending orders yet to be shipped' }}
        </h1>
        <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" width="100%">
            <tr>
                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">&nbsp;</td>
                <td class="receipt-container" style="font-family: sans-serif; font-size: 14px; vertical-align: top; width: 80%;" width="80%" valign="top">
                    <table class="receipt" border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; Margin-bottom: 20px;" width="100%">
                        <tr class="receipt-subtle" style="color: #aaa;">
                            <td style="font-family: sans-serif; font-size: 14px; vertical-align: top; border-bottom: 1px solid #eee; margin: 0; padding: 5px;" valign="top">Description</td>
                            <td class="align-right" style="font-family: sans-serif; font-size: 14px; vertical-align: top; text-align: right; border-bottom: 1px solid #eee; margin: 0; padding: 5px;" valign="top" align="right">Amount</td>
                        </tr>
                        @foreach ($collection as $order)
                            <tr>
                                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top; border-bottom: 1px solid #eee; margin: 0; padding: 5px;" valign="top">
                                    <strong>{{ $order->id }}</strong><br>

                                    <ul style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 0px; Margin-left: 5px; padding: 0; text-indent: 0;">
                                        @foreach ($order->products as $product)
                                            <li style="list-style-position: outside; Margin-left: 15px; padding: 0; text-indent: 0;">{{ $product->name }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td class="receipt-figure" style="font-family: sans-serif; font-size: 14px; vertical-align: bottom; border-bottom: 1px solid #eee; margin: 0; padding: 5px; text-align: right;" valign="bottom" align="right">
                                    {{ $order->display('amount') }}
                                </td>
                            </tr>
                        @endforeach
                    </table>
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">Clear pending shipping orders in the HitPay Web Dashboard by navigating to Orders > Pending.<br><a href="https://dashboard.hit-pay.com" target="_blank">https://dashboard.hit-pay.com</a></p>
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">Team HitPay</p>
                </td>
                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">&nbsp;</td>
            </tr>
        </table>
    </td>
@endsection
