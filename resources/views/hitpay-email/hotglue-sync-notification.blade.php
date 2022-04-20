@extends('hitpay-email.layouts.base', [
    'title' => $subject,
    'preheader' => $subject,
])

@section('content')
    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">
        <div style="text-align: center">
            <img style="color: #222222; font-family: sans-serif; font-weight: 300; line-height: 1.4; margin: 0; Margin-bottom: 30px; font-size: 35px; text-transform: capitalize;" src="{{ asset('hitpay/logo-000036.png') }}" width="128">
        </div>
        <h1 style="color: #222222; font-family: sans-serif; font-weight: 300; line-height: 1.4; margin: 0; Margin-bottom: 30px; font-size: 24px; text-align: center; text-transform: capitalize;">{{ $subject }}</h1>
        <p style="color: #222222; font-family: sans-serif; font-weight: 300; line-height: 1.4; margin: 0; Margin-bottom: 30px; font-size: 20px; text-align: center; text-transform: capitalize;">
            {{ $messageText }}
        </p>
        <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" width="100%">
            <tr>
                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">&nbsp;</td>
                <td class="receipt-container" style="font-family: sans-serif; font-size: 14px; vertical-align: top; width: 80%;" width="80%" valign="top">
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">Reach out to us at <a href="mailto:support@hit-pay.com" target="_blank" style="color: #3498db; text-decoration: underline;">support@hit-pay.com</a> if you have any questions or suggestions.</p>
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">Team HitPay</p>
                </td>
                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">&nbsp;</td>
            </tr>
        </table>
    </td>
@endsection
