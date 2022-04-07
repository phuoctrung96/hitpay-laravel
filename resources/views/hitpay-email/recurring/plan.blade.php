<!doctype html>
<html>
<head>
    <meta name="viewport" content="width=device-width">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Receipt</title>
    <style media="all" type="text/css">
        @media only screen and (max-width: 620px) {
            .span-2,
            .span-3 {
                max-width: none !important;
                width: 100% !important;
            }

            .span-2 > table,
            .span-3 > table {
                max-width: 100% !important;
                width: 100% !important;
            }
        }

        @media all {
            .btn-primary table td:hover {
                background-color: #34495e !important;
            }

            .btn-primary a:hover {
                background-color: #34495e !important;
                border-color: #34495e !important;
            }
        }

        @media all {
            .btn-secondary a:hover {
                border-color: #34495e !important;
                color: #34495e !important;
            }
        }

        @media only screen and (max-width: 620px) {
            h1 {
                font-size: 28px !important;
                margin-bottom: 10px !important;
            }

            h2 {
                font-size: 22px !important;
                margin-bottom: 10px !important;
            }

            h3 {
                font-size: 16px !important;
                margin-bottom: 10px !important;
            }

            .main p,
            .main ul,
            .main ol,
            .main td,
            .main span {
                font-size: 16px !important;
            }

            .wrapper {
                padding: 10px !important;
            }

            .article {
                padding-left: 0 !important;
                padding-right: 0 !important;
            }

            .content {
                padding: 0 !important;
            }

            .container {
                padding: 0 !important;
                width: 100% !important;
            }

            .header {
                margin-bottom: 10px !important;
            }

            .main {
                border-left-width: 0 !important;
                border-radius: 0 !important;
                border-right-width: 0 !important;
            }

            .btn table {
                max-width: 100% !important;
                width: 100% !important;
            }

            .btn a {
                max-width: 100% !important;
                padding: 12px 5px !important;
                width: 100% !important;
            }

            .img-responsive {
                height: auto !important;
                max-width: 100% !important;
                width: auto !important;
            }

            .alert td {
                border-radius: 0 !important;
                padding: 10px !important;
            }

            .receipt {
                width: 100% !important;
            }

            hr {
                Margin-bottom: 10px !important;
                Margin-top: 10px !important;
            }

            .hr tr:first-of-type td,
            .hr tr:last-of-type td {
                height: 10px !important;
                line-height: 10px !important;
            }
        }

        @media all {
            .ExternalClass {
                width: 100%;
            }

            .ExternalClass,
            .ExternalClass p,
            .ExternalClass span,
            .ExternalClass font,
            .ExternalClass td,
            .ExternalClass div {
                line-height: 100%;
            }

            .apple-link a {
                color: inherit !important;
                font-family: inherit !important;
                font-size: inherit !important;
                font-weight: inherit !important;
                line-height: inherit !important;
                text-decoration: none !important;
            }
        }
    </style>

    <!--[if gte mso 9]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:AllowPNG/>
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml><![endif]-->
</head>
@php($style = [
    'body' => 'font-family: sans-serif; -webkit-font-smoothing: antialiased; font-size: 14px; line-height: 1.4; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; background-color: #f6f6f6; margin: 0; padding: 0;',
    'special_1' => 'border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;',
    'outer_table' => 'border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; background-color: #f6f6f6;',
    'outer_td' => 'font-family: sans-serif; font-size: 14px; vertical-align: top;',
    'outer_td_ext' => 'Margin: 0 auto !important; max-width: 580px; padding: 10px; width: 580px;',
    'content' => 'box-sizing: border-box; display: block; Margin: 0 auto; max-width: 580px; padding: 10px;',
    'preheader' => 'color: transparent; display: none; height: 0; max-height: 0; max-width: 0; opacity: 0; overflow: hidden; mso-hide: all; visibility: hidden; width: 0;',
    'header' => 'background-color: #fff; padding-bottom: 20px; padding-top: 20px; width: 100%; border-top-left-radius: 3px; border-top-right-radius: 3px;',
    'inner_table' => 'border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; min-width: 100%;',
    'link' => 'color: #3498db; text-decoration: underline;',
    'img' => 'border: none; -ms-interpolation-mode: bicubic; max-width: 100%;',
    'social_btn' => 'text-decoration: underline; color: #999999; font-size: 12px; text-align: center;',
    'details' => 'font-family: sans-serif; vertical-align: top; margin: 0; padding: 5px; font-size: 18px;',
    'help' => 'font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;',
    'out_table' => 'border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; background: #fff; border-radius: 3px;',
    'title' => 'color: #222222; font-family: sans-serif; font-weight: 300; line-height: 1.4; margin: 0; Margin-bottom: 30px; font-size: 35px; text-align: center; text-transform: capitalize;',
    'normal_text' => 'font-family: sans-serif; vertical-align: top; padding-top: 10px; padding-bottom: 10px; font-size: 12px; color: #999999; text-align: center;',
    'footer_table' => 'border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: auto; Margin: 0 auto; text-align: center;',
    'footer_td' => 'font-family: sans-serif; vertical-align: top; font-size: 12px; color: #999999; text-align: center;',
    'social_icon' => 'height: 44px; Margin: 0 2px;',
    'address' => 'color: #999999; font-size: 12px; text-align: center; text-decoration: none;',
    'innest_table' => 'border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; Margin-bottom: 20px;',
])
<body style="{{ $style['body'] }}">
<table border="0" cellpadding="0" cellspacing="0" class="body" style="{{ $style['outer_table'] }}" width="100%"
       bgcolor="#f6f6f6">
    <tr>
        <td style="{{ $style['outer_td'] }}" valign="top">&nbsp;</td>
        <td class="container" style="{{ $style['outer_td'] }} {{ $style['outer_td_ext'] }}" width="580" valign="top">
            <div class="content" style="{{ $style['content'] }}">
                <span class="preheader" style="{{ $style['preheader'] }}">Receipt from {{ $business_name }}.</span>
                <div class="header" style="{{ $style['header'] }}">
                    <table border="0" cellpadding="0" cellspacing="0" style="{{ $style['inner_table'] }}" width="100%">
                        <tr>
                            <td class="align-center" style="{{ $style['outer_td'] }} text-align: center;" valign="top"
                                align="center">
                                @if (isset($business_logo))
                                    <img src="{{ $business_logo }}" height="48" alt="Logo" align="center" style="{{ $style['img'] }}">
                                @endif
                                <h1>{{ $business_name }}</h1>
                            </td>
                        </tr>
                    </table>
                </div>
                <table border="0" cellpadding="0" cellspacing="0" class="main" style="{{ $style['out_table'] }}" width="100%">
                    <tr>
                        <td class="wrapper" style="{{ $style['outer_td'] }} box-sizing: border-box; padding: 20px;" valign="top">
                            <table border="0" cellpadding="0" cellspacing="0" style="{{ $style['special_1'] }}" width="100%">
                                <tr>
                                    <td style="{{ $style['outer_td'] }}" valign="top">
                                        <h1 style="color: #222222; font-family: sans-serif; font-weight: 300; line-height: 1.4; margin: 0; Margin-bottom: 30px; font-size: 25px; text-transform: capitalize; text-align: center">{{ $title }}</h1>
                                        <table border="0" cellpadding="0" cellspacing="0" style="{{ $style['special_1'] }}"
                                               width="100%">
                                            <tr>
                                                <td style="{{ $style['outer_td'] }}" valign="top">&nbsp;</td>
                                                <td class="receipt-container" style="{{ $style['outer_td'] }} width: 80%;"
                                                    width="80%" valign="top">
                                                    <table class="receipt" border="0" cellpadding="0" cellspacing="0"
                                                           style="{{ $style['innest_table'] }}" width="100%">
                                                        <tr class="receipt-bold">
                                                            <td style="{{ $style['details'] }}" valign="top">Plan Name</td>
                                                            <td class="receipt-figure" style="{{ $style['details'] }} font-weight: 600;"
                                                                valign="top" align="right">{{ $plan_name }}</td>
                                                        </tr>
                                                        <tr class="receipt-bold">
                                                            <td style="{{ $style['details'] }}" valign="top">Recurring Plan #</td>
                                                            <td class="receipt-figure" style="{{ $style['details'] }} font-weight: 600;"
                                                                valign="top" align="right">{{ $plan_id }}</td>
                                                        </tr>
                                                        @if ($plan_status === 'scheduled')
                                                            <tr class="receipt-bold">
                                                                <td style="{{ $style['details'] }}" valign="top">Start Date</td>
                                                                <td class="receipt-figure" style="{{ $style['details'] }} font-weight: 600;"
                                                                    valign="top" align="right">{{ $plan_date }}</td>
                                                            </tr>
                                                        @endif
                                                        @if ($plan_description)
                                                            <tr class="receipt-bold">
                                                                <td style="{{ $style['details'] }}" valign="top" colspan="2">Description</td>
                                                            </tr>
                                                            <tr class="receipt-bold">
                                                                <td class="receipt-figure" style="font-family: sans-serif; vertical-align: top; margin: 0; padding: 5px; font-size: 14px; font-weight: 400; color: #999; text-align: justify" valign="top" align="right" colspan="2">{{ $plan_description ?? '-' }}</td>
                                                            </tr>
                                                        @endif
                                                        <tr class="receipt-bold">
                                                            <td style="{{ $style['details'] }}" valign="top">Customer Name</td>
                                                            <td class="receipt-figure" style="{{ $style['details'] }} font-weight: 600;"
                                                                valign="top" align="right">{{ $customer['name'] ?? '-' }}</td>
                                                        </tr>
                                                        <tr class="receipt-bold">
                                                            <td style="{{ $style['details'] }}" valign="top">Customer Email</td>
                                                            <td class="receipt-figure" style="{{ $style['details'] }} font-weight: 600;"
                                                                valign="top" align="right">{{ $customer['email'] ?? '-' }}</td>
                                                        </tr>
                                                        <tr class="receipt-bold">
                                                            <td style="{{ $style['details'] }}" valign="top">Billing Cycle</td>
                                                            <td class="receipt-figure" style="{{ $style['details'] }} font-weight: 600;"
                                                                valign="top" align="right">{{ ucfirst($plan_cycle) }}</td>
                                                        </tr>
                                                        @if ($plan_status === 'active')
                                                            <tr class="receipt-bold">
                                                                <td style="{{ $style['details'] }}" valign="top">Next Charge Date</td>
                                                                <td class="receipt-figure" style="{{ $style['details'] }} font-weight: 600;"
                                                                    valign="top" align="right">{{ $plan_next_charge_date }}</td>
                                                            </tr>
                                                        @endif
                                                        <tr class="receipt-bold">
                                                            <td style="{{ $style['details'] }}" valign="top">Amount Due</td>
                                                            <td class="receipt-figure" style="{{ $style['details'] }} font-weight: 600;"
                                                                valign="top" align="right">{{ $plan_price }}</td>
                                                        </tr>
                                                    </table>
                                                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;"><a href="{{ $plan_url }}">
                                                            @if ($plan_status === 'active')
                                                                Click here to pay this invoice
                                                            @else
                                                                Click here to view this invoice
                                                            @endif
                                                        </a></p>
                                                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">If you have any questions about this invoice, please contact <a href="mailto:{{ $business_email }}" target="_blank" style="color: #3498db; text-decoration: underline;">{{ $business_email }}</a></p>
                                                </td>
                                                <td style="{{ $style['outer_td'] }}" valign="top">&nbsp;</td>
                                            </tr>
                                        </table>
                                        <hr style="border-color: transparent; margin-bottom: 30px">
                                        <h6 style="color: #222222; font-family: sans-serif; font-weight: 300; line-height: 1.4; margin: 0; Margin-bottom: 30px; font-size: 14px; text-transform: capitalize; text-align: center">Powered by HitPay</h6>
                                        <p class="align-center" style="text-align: center;" valign="top" align="center">
                                        <img src="{{ asset('hitpay/logo-000036.png') }}" height="48" alt="Logo" align="center" style="{{ $style['img'] }}">
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
        </td>
        <td style="{{ $style['outer_td'] }}" valign="top">&nbsp;</td>
    </tr>
</table>
</body>
</html>
