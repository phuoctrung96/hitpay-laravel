@extends('hitpay-email.layouts.base', [
    'title' => $title,
    'preheader' => $title,
])

@section('content')
    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">
        <div style="text-align: center">
            @if (isset($business_logo))
                <img style="color: #222222; font-family: sans-serif; font-weight: 300; line-height: 1.4; margin: 0; Margin-bottom: 5px; font-size: 35px; text-transform: capitalize;" src="{{ $business_logo }}" width="48">
            @endif
            <h2 style="color: #222222; font-family: sans-serif; font-weight: bold; line-height: 1.4; margin: 0; Margin-bottom: 30px; font-size: 35px; text-transform: capitalize;">{{ $title }}</h2>
            <h1 style="color: #222222; font-family: sans-serif; font-weight: 300; line-height: 1.4; margin: 0; Margin-bottom: 30px; font-size: 25px; text-transform: capitalize;">
                {{ $subtitle }}
            </h1>
        </div>
        <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" width="100%">
            <tr>
                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">&nbsp;</td>
                <td class="receipt-container" style="font-family: sans-serif; font-size: 14px; vertical-align: top; width: 80%;" width="80%" valign="top">
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0;">Order ID : <strong>951dfef1-74ca-440e-b8c1-0a45f46a537c</strong></p>
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">Ordered At : <strong>2022.05.24 16:30</strong></p>
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: bold; Margin: 0;">Shipping to :</p>
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">John Doe</p>
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">New York City No 5B</p>
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: bold; Margin: 0; margin-bottom: 15px;">Remark : Please check before ship</p>


                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0;">
                        {{ $store_information_title }}
                    </p>

                    <p style="font-family: sans-serif; font-size: 14px; font-weight: bold; Margin: 0; Margin-bottom: 15px;">
                        {{ $store_information_value }}
                    </p>

                    <table class="receipt" border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; Margin-bottom: 20px;" width="100%">
                        <tr class="receipt-subtle" style="color: #aaa;">
                            <td style="font-family: sans-serif; font-size: 14px; vertical-align: top; border-bottom: 1px solid #eee; margin: 0; padding: 5px;" valign="top">Items</td>
                            <td class="align-right" style="font-family: sans-serif; font-size: 14px; vertical-align: top; text-align: right; border-bottom: 1px solid #eee; margin: 0; padding: 5px;" valign="top" align="right">Amount</td>
                        </tr>
                        <tr>
                            <td style="font-family: sans-serif; font-size: 14px; vertical-align: top; border-bottom: 1px solid #eee; margin: 0; padding: 5px;" valign="top">
                                <strong>Apple airpods</strong>
                                <small class="text-muted">x 1</small>
                            </td>
                            <td class="receipt-figure" style="font-family: sans-serif; font-size: 14px; vertical-align: top; border-bottom: 1px solid #eee; margin: 0; padding: 5px; text-align: right;" valign="top" align="right">
                                SGD 150
                            </td>
                        </tr>
                        <tr class="receipt-subtle" style="color: #aaa;">
                            <td style="font-family: sans-serif; font-size: 14px; vertical-align: top; border-bottom: 1px solid #eee; margin: 0; padding: 5px;" valign="top">
                                - Shipping Method: Flat
                            </td>
                            <td class="receipt-figure" style="font-family: sans-serif; font-size: 14px; vertical-align: top; border-bottom: 1px solid #eee; margin: 0; padding: 5px; text-align: right;" valign="top" align="right">
                                SGD 0
                            </td>
                        </tr>

                        <tr class="receipt-subtle" style="color: #aaa;">
                            <td style="font-family: sans-serif; font-size: 14px; vertical-align: top; border-bottom: 1px solid #eee; margin: 0; padding: 5px;" valign="top">
                                - Discount
                            </td>
                            <td class="receipt-figure" style="font-family: sans-serif; font-size: 14px; vertical-align: top; border-bottom: 1px solid #eee; margin: 0; padding: 5px; text-align: right;" valign="top" align="right">
                                - SGD 0
                            </td>
                        </tr>

                        <tr class="receipt-subtle" style="color: #aaa;">
                            <td style="font-family: sans-serif; font-size: 14px; vertical-align: top; border-bottom: 1px solid #eee; margin: 0; padding: 5px;" valign="top">
                                - Coupon Discount
                            </td>
                            <td class="receipt-figure" style="font-family: sans-serif; font-size: 14px; vertical-align: top; border-bottom: 1px solid #eee; margin: 0; padding: 5px; text-align: right;" valign="top" align="right">
                                - SGD 0
                            </td>
                        </tr>

                        <tr class="receipt-bold">
                            <td style="font-family: sans-serif; vertical-align: top; margin: 0; padding: 5px; font-size: 12px; border-bottom: 1px solid #eee; font-weight: 600;" valign="top">Sub Total</td>
                            <td class="receipt-figure" style="font-family: sans-serif; vertical-align: top; margin: 0; padding: 5px; font-size: 15px; border-bottom: 1px solid #eee; text-align: right; font-weight: 600;" valign="top" align="right">
                                SGD 150
                            </td>
                        </tr>

                        <tr class="receipt-bold">
                            <td style="font-family: sans-serif; vertical-align: top; margin: 0; padding: 5px; font-size: 15px; border-bottom: 1px solid #eee; font-weight: 600;" valign="top">Tax</td>
                            <td class="receipt-figure" style="font-family: sans-serif; vertical-align: top; margin: 0; padding: 5px; font-size: 15px; border-bottom: 1px solid #eee; text-align: right; font-weight: 600;" valign="top" align="right">
                                SGD 2
                            </td>
                        </tr>
                        <tr class="receipt-bold">
                            <td style="font-family: sans-serif; vertical-align: top; margin: 0; padding: 5px; font-size: 18px; border-bottom: 2px solid #333; border-top: 2px solid #333; font-weight: 600;" valign="top">Total</td>
                            <td class="receipt-figure" style="font-family: sans-serif; vertical-align: top; margin: 0; padding: 5px; font-size: 18px; border-bottom: 2px solid #333; text-align: right; border-top: 2px solid #333; font-weight: 600;" valign="top" align="right">
                                SGD 152
                            </td>
                        </tr>
                    </table>
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">
                        {!! $footer !!}
                    </p>
                </td>
                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">&nbsp;</td>
            </tr>
        </table>
    </td>
@endsection
