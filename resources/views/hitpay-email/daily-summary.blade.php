@extends('hitpay-email.layouts.base', [
    'title' => 'Your Daily Collection for '.$date,
    'preheader' => 'Your total collections using HitPay for '.$date.'.',
])

@section('content')
    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">
        <h1 style="color: #222222; font-family: sans-serif; font-weight: 300; line-height: 1.4; margin: 0; Margin-bottom: 30px; font-size: 35px; text-align: center; text-transform: capitalize;">Thank you for using HitPay!</h1>
        <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" width="100%">
            <tr>
                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">&nbsp;</td>
                <td class="receipt-container" style="font-family: sans-serif; font-size: 14px; vertical-align: top; width: 80%;" width="80%" valign="top">
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">Your total collections using HitPay for {{ $date }} is as follows :</p>
                    <table class="receipt" border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; Margin-bottom: 20px;" width="100%">
                        <tr class="receipt-subtle" style="color: #aaa;">
                            <td style="font-family: sans-serif; font-size: 14px; vertical-align: top; border-bottom: 1px solid #eee; margin: 0; padding: 5px;" valign="top">Currency</td>
                            <td class="align-right" style="font-family: sans-serif; font-size: 14px; vertical-align: top; text-align: right; border-bottom: 1px solid #eee; margin: 0; padding: 5px;" valign="top" align="right">Amount</td>
                        </tr>
                        @foreach ($currencies as $value)
                            <tr>
                                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top; border-bottom: 1px solid #eee; margin: 0; padding: 5px;" valign="top">
                                    <strong>{{ strtoupper($value['code']) }}</strong> @lang('misc.currency.'.$value['code'])
                                </td>
                                <td class="receipt-figure" style="font-family: sans-serif; font-size: 14px; vertical-align: top; border-bottom: 1px solid #eee; margin: 0; padding: 5px; text-align: right;" valign="top" align="right">
                                    {{ $value['amount'] }}
                                </td>
                            </tr>
                        @endforeach
                    </table>
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">You can view and export your transaction history by navigating to Sales and Reports in the HitPay App</p>
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">Reach out to us at <a href="mailto:support@hit-pay.com" target="_blank" style="color: #3498db; text-decoration: underline;">support@hit-pay.com</a> if you have any questions or suggestions.</p>
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">Team HitPay</p>
                </td>
                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">&nbsp;</td>
            </tr>
        </table>
    </td>
@endsection
