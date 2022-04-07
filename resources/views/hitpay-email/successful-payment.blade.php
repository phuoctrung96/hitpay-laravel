@extends('hitpay-email.layouts.base', [
    'title' => $title,
    'preheader' => 'New Payment',
])

@section('content')
    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">
        <div style="text-align: center">
            <img style="color: #222222; font-family: sans-serif; font-weight: 300; line-height: 1.4; margin: 0; Margin-bottom: 30px; font-size: 35px; text-transform: capitalize;" src="{{ asset('hitpay/logo-000036.png') }}" width="128">
        </div>
        <h1 style="color: #222222; font-family: sans-serif; font-weight: 300; line-height: 1.4; margin: 0; Margin-bottom: 30px; font-size: 25px; text-align: center; text-transform: capitalize;">
            You received new payment of {{getFormattedAmount($charge->currency, $charge->amount)}}
        </h1>
        <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" width="100%">
            <tr>
                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">&nbsp;</td>
                <td class="receipt-container" style="font-family: sans-serif; font-size: 14px; vertical-align: top; width: 80%;" width="80%" valign="top">
                    @if ($charge->channel === \App\Enumerations\Business\Channel::PAYMENT_GATEWAY)
                        @if ($charge->plugin_provider_order_id)
                            <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">Order ID: {{$charge->plugin_provider_order_id}} (REF: {{$charge->plugin_provider_reference}})</p>
                        @else <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">REF: {{$charge->plugin_provider_reference}}</p>
                        @endif
                    @endif
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">Log in to the
                        <a href="{{route('login')}}" style="color: #3498db; text-decoration: underline;">HitPay dashboard</a> to view your account’s transaction history.</p>
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">Team HitPay</p>
                </td>
                <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;" valign="top">&nbsp;</td>
            </tr>
        </table>
    </td>
@endsection
