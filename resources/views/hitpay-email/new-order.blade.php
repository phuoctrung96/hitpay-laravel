@extends('hitpay-email.layouts.base', [
    'title' => $title,
    'preheader' => 'View order details below',
])

@section('content')
    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">
        <h1 style="color: #222222; font-family: sans-serif; font-weight: 300; line-height: 1.4; margin: 0; Margin-bottom: 30px; font-size: 35px; text-align: center; text-transform: capitalize;">View order details below</h1>
        <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" width="100%">
            <tr>
                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">&nbsp;</td>
                <td class="receipt-container" style="font-family: sans-serif; font-size: 14px; vertical-align: top; width: 80%;" width="80%" valign="top">
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">Order ID : <strong>{{ $order_id }}</strong></p>
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: bold; Margin: 0;">Buyer Information</p>
                    @if (isset($shipping))
                        <p style="font-family: sans-serif; font-size: 14px; font-weight: bold; Margin: 0;">Shipping to :</p>
                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">
                            @if (isset($customer['name']))
                                {{ $customer['name'] }}
                                @if ($customer['email'])
                                    {{ $customer['email'] }}
                                @endif
                            @elseif ($customer['email'])
                                {{ $customer['email'] }}
                            @endif
                        </p>
                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">{{ $customer['address'] }}</p>
                        @if (isset($order_remark))
                            <p style="font-family: sans-serif; font-size: 14px; font-weight: bold; Margin: 0;">Remark :</p>
                            <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">{{ $order_remark }}</p>
                        @endif
                    @else
                        <p style="font-family: sans-serif; font-size: 14px; font-weight: bold; Margin: 0;">Your Information :</p>
                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">
                            @if (isset($customer['name']))
                                {{ $customer['name'] }}
                                @if ($customer['email'])
                                    {{ $customer['email'] }}
                                @endif
                            @elseif ($customer['email'])
                                {{ $customer['email'] }}
                            @endif
                        </p>
                        <p style="font-family: sans-serif; font-size: 14px; font-weight: bold; Margin: 0;">Pickup Address :</p>
                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">{{ $business_address ?? 'Inform Buyer' }}</p>
                    @endif
                    <table class="receipt" border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; Margin-bottom: 20px;" width="100%">
                        <tr class="receipt-subtle" style="color: #aaa;">
                            <td style="font-family: sans-serif; font-size: 14px; vertical-align: top; border-bottom: 1px solid #eee; margin: 0; padding: 5px;" valign="top">Items</td>
                            <td class="align-right" style="font-family: sans-serif; font-size: 14px; vertical-align: top; text-align: right; border-bottom: 1px solid #eee; margin: 0; padding: 5px;" valign="top" align="right">Amount</td>
                        </tr>
                        @foreach ($ordered_products as $product)
                            <tr>
                                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top; border-bottom: 1px solid #eee; margin: 0; padding: 5px;" valign="top">
                                    <strong>{{ $product['name'] }}</strong>
                                    @if ($product['description'])
                                        <small class="text-muted">({{ $product['description'] }})</small>
                                    @endif
                                    @if ($product['remark'])
                                        <small class="text-muted">({{ $product['remark'] }})</small>
                                    @endif
                                    <small class="text-muted">x {{ $product['quantity'] }}</small>
                                </td>
                                <td class="receipt-figure" style="font-family: sans-serif; font-size: 14px; vertical-align: top; border-bottom: 1px solid #eee; margin: 0; padding: 5px; text-align: right;" valign="top" align="right">
                                    {{ $product['price'] }}
                                </td>
                            </tr>
                        @endforeach
                        @if (isset($shipping))
                            <tr class="receipt-subtle" style="color: #aaa;">
                                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top; border-bottom: 1px solid #eee; margin: 0; padding: 5px;" valign="top">
                                    - Shipping Method: {{ $shipping['method'] }}
                                </td>
                                <td class="receipt-figure" style="font-family: sans-serif; font-size: 14px; vertical-align: top; border-bottom: 1px solid #eee; margin: 0; padding: 5px; text-align: right;" valign="top" align="right">
                                    {{ $shipping['amount'] }}
                                </td>
                            </tr>
                        @endif
                        @if (isset($discount))
                            <tr class="receipt-subtle" style="color: #aaa;">
                                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top; border-bottom: 1px solid #eee; margin: 0; padding: 5px;" valign="top">
                                    - {{ $discount['name'] }}
                                </td>
                                <td class="receipt-figure" style="font-family: sans-serif; font-size: 14px; vertical-align: top; border-bottom: 1px solid #eee; margin: 0; padding: 5px; text-align: right;" valign="top" align="right">
                                    - {{ $discount['amount'] }}
                                </td>
                            </tr>
                        @endif
                        @if(isset($coupon_amount))
                            <tr class="receipt-subtle" style="color: #aaa;">
                                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top; border-bottom: 1px solid #eee; margin: 0; padding: 5px;" valign="top">
                                    - Coupon Discount
                                </td>
                                <td class="receipt-figure" style="font-family: sans-serif; font-size: 14px; vertical-align: top; border-bottom: 1px solid #eee; margin: 0; padding: 5px; text-align: right;" valign="top" align="right">
                                    - {{ $coupon_amount }}
                                </td>
                            </tr>
                        @endif
                        <tr class="receipt-bold">
                            <td style="font-family: sans-serif; vertical-align: top; margin: 0; padding: 5px; font-size: 12px; border-bottom: 1px solid #eee; font-weight: 600;" valign="top">Sub Total</td>
                            <td class="receipt-figure" style="font-family: sans-serif; vertical-align: top; margin: 0; padding: 5px; font-size: 15px; border-bottom: 1px solid #eee; text-align: right; font-weight: 600;" valign="top" align="right">
                                {{ $sub_total_amount }}
                            </td>
                        </tr>
                        <tr class="receipt-bold">
                            <td style="font-family: sans-serif; vertical-align: top; margin: 0; padding: 5px; font-size: 15px; border-bottom: 1px solid #eee; font-weight: 600;" valign="top">Tax</td>
                            <td class="receipt-figure" style="font-family: sans-serif; vertical-align: top; margin: 0; padding: 5px; font-size: 15px; border-bottom: 1px solid #eee; text-align: right; font-weight: 600;" valign="top" align="right">
                                {{ $tax_amount }}
                            </td>
                        </tr>
                        <tr class="receipt-bold">
                            <td style="font-family: sans-serif; vertical-align: top; margin: 0; padding: 5px; font-size: 18px; border-bottom: 2px solid #333; border-top: 2px solid #333; font-weight: 600;" valign="top">Total</td>
                            <td class="receipt-figure" style="font-family: sans-serif; vertical-align: top; margin: 0; padding: 5px; font-size: 18px; border-bottom: 2px solid #333; text-align: right; border-top: 2px solid #333; font-weight: 600;" valign="top" align="right">
                                {{ $order_amount }}
                            </td>
                        </tr>
                    </table>
                    @if (isset($shipping))
                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">Update shipping status of order in HitPay app or HitPay Web Dashboard under Pending Shipping Orders. View customer payment details of above order under Transactions. Reach out to us at <a href="mailto:support@hit-pay.com" target="_blank" style="color: #3498db; text-decoration: underline;">support@hit-pay.com</a> if you have any questions or suggestions.</p>
                    @else
                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">Check transactions details in the mobile app under transactions or check detailed order information by logging in to the HitPay Web Dashboard under Orders > Completed</p>
                    @endif
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">Team HitPay</p>
                </td>
                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">&nbsp;</td>
            </tr>
        </table>
    </td>
@endsection
