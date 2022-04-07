@extends('hitpay-email.layouts.base', [
    'title' => 'Thank you for your order',
    'preheader' => 'Thank you for your order',
])

@section('content')
    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">
        <div style="text-align: center">
            @if (isset($business_logo))
                <img style="color: #222222; font-family: sans-serif; font-weight: 300; line-height: 1.4; margin: 0; Margin-bottom: 5px; font-size: 35px; text-transform: capitalize;" src="{{ $business_logo }}" width="48">
            @endif
            <h2 style="color: #222222; font-family: sans-serif; font-weight: bold; line-height: 1.4; margin: 0; Margin-bottom: 30px; font-size: 35px; text-transform: capitalize;">{{ $business_name }}</h2>
            <h1 style="color: #222222; font-family: sans-serif; font-weight: 300; line-height: 1.4; margin: 0; Margin-bottom: 30px; font-size: 25px; text-transform: capitalize;">View order details below</h1>
        </div>
        <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" width="100%">
            <tr>
                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">&nbsp;</td>
                <td class="receipt-container" style="font-family: sans-serif; font-size: 14px; vertical-align: top; width: 80%;" width="80%" valign="top">
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0;">Order ID :
                        <strong>{{ $order_id }}</strong></p>
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">Ordered At :
                        <strong>{{ $order_date }}</strong></p>
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
                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">{{ $business_address ?? 'Contact seller' }}</p>
                    @endif
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: bold; Margin: 0;">Seller Information :</p>
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">{{ $business_name }} ({{ $business_email }})</p>
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
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">Notice something wrong?
                        <a href="mailto:{{ $business_email }}" target="_blank" style="color: #3498db; text-decoration: underline;">Contact our support team</a>
                        and we'll be happy to help.
                    </p>
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">{{ $business_name }}</p>
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">Charge ID: {{ $charge_id }}</p>
                    @isset($application)
                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">Application Name: {{ $application['name'] }} AID: {{ $application['identifier'] }}</p>
                    @endisset
                </td>
                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">&nbsp;</td>
            </tr>
        </table>
    </td>
@endsection
