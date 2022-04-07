@extends('hitpay-email.layouts.base', [
    'title' => $title,
    'preheader' => $out_of_stock ? $product->product->name.' : Out of Stock' : 'Low Quantity Alert : '.$product->product->name,
])

@section('content')
    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">
        <h1 style="color: #222222; font-family: sans-serif; font-weight: 300; line-height: 1.4; margin: 0; Margin-bottom: 30px; font-size: 25px; text-align: center; text-transform: capitalize;">
            {{ $out_of_stock ? 'The below product is out of stock' : 'The below product is running out of stock' }}
        </h1>
        <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" width="100%">
            <tr>
                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">&nbsp;</td>
                <td class="receipt-container" style="font-family: sans-serif; font-size: 14px; vertical-align: top; width: 80%;" width="80%" valign="top">
                    <h2 style="color: #222222; font-family: sans-serif; font-weight: 400; line-height: 1.4; margin: 0; Margin-bottom: 15px; font-size: 28px;">
                        {{ $product->product->name }}
                    </h2>
                    @if ($out_of_stock)
                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">Update product quantity or archive product checkout under Checkouts in the HitPay App. Reach out to us at <a href="mailto:support@hit-pay.com" target="_blank" style="color: #3498db; text-decoration: underline;">support@hit-pay.com</a> if you have any questions or suggestions.</p>
                    @else
                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">Quantity remaining : <strong>{{ $product->quantity }}</strong></p>
                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">Update product quantity under Products in the HitPay app. Reach out to us at <a href="mailto:support@hit-pay.com" target="_blank" style="color: #3498db; text-decoration: underline;">support@hit-pay.com</a> if you have any questions or suggestions.</p>
                    @endif
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">Team HitPay</p>
                </td>
                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">&nbsp;</td>
            </tr>
        </table>
    </td>
@endsection
