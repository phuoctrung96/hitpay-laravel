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
                <span class="preheader" style="{{ $style['preheader'] }}">
                    @if($isHaveTemplateEmail && $title)
                        {{ $title }}
                    @else
                        Receipt from {{ $business_name }}.
                    @endif
                </span>
                <div class="header" style="{{ $style['header'] }}">
                    <table border="0" cellpadding="0" cellspacing="0" style="{{ $style['inner_table'] }}" width="100%">
                        <tr>
                            <td class="align-center" style="{{ $style['outer_td'] }} text-align: center;" valign="top"
                                align="center">
                                @if (isset($business_logo))
                                    <img src="{{ $business_logo }}" height="48" alt="Logo" align="center" style="{{ $style['img'] }}">
                                @endif
                                <h1>
                                    @if($isHaveTemplateEmail && $title)
                                        {{ $title }}
                                    @else
                                        {{ $business_name }}
                                    @endif
                                </h1>
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
                                        <h1 style="color: #222222; font-family: sans-serif; font-weight: 300; line-height: 1.4; margin: 0; Margin-bottom: 30px; font-size: 25px; text-transform: capitalize; text-align: center">
                                            @if($isHaveTemplateEmail && $subtitle)
                                                {{ $subtitle }}
                                            @else
                                                View transaction details below
                                            @endif
                                        </h1>
                                        <table border="0" cellpadding="0" cellspacing="0" style="{{ $style['special_1'] }}"
                                               width="100%">
                                            <tr>
                                                <td style="{{ $style['outer_td'] }}" valign="top">&nbsp;</td>
                                                <td class="receipt-container" style="{{ $style['outer_td'] }} width: 80%;"
                                                    width="80%" valign="top">
                                                    <table class="receipt" border="0" cellpadding="0" cellspacing="0"
                                                           style="{{ $style['innest_table'] }}" width="100%">
                                                        <tr class="receipt-bold">
                                                            <td style="{{ $style['details'] }}" valign="top">Description</td>
                                                            <td class="receipt-figure" style="{{ $style['details'] }} font-weight: 600;"
                                                                valign="top" align="right">
                                                                @if (isset($target_name))
                                                                    @php($combined_remarks = array_filter([$recurring_plan_dbs_dda_reference, $charge_remark]))
                                                                    @php($combined_remarks = count($combined_remarks) ? implode(', ', $combined_remarks) : '-')
                                                                    {{ $target_name }} ({{ $combined_remarks ?? '-' }})
                                                                @else
                                                                    {{ $charge_remark ?? '-' }}
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr class="receipt-bold">
                                                            <td style="{{ $style['details'] }}" valign="top">Date Paid</td>
                                                            <td class="receipt-figure" style="{{ $style['details'] }} font-weight: 600;"
                                                                valign="top" align="right">{{ $charge_date }}</td>
                                                        </tr>
                                                        <tr class="receipt-bold">
                                                            <td style="{{ $style['details'] }}" valign="top">Payment Method</td>
                                                            <td class="receipt-figure" style="{{ $style['details'] }} font-weight: 600;"
                                                                valign="top" align="right">{{ $charge_method }}</td>
                                                        </tr>
                                                        @if ($charge_method == "PayNow Online")
                                                            <td style="{{ $style['details'] }}" valign="top">PayNow Reference</td>
                                                            <td class="receipt-figure" style="{{ $style['details'] }} font-weight: 600;"
                                                                valign="top" align="right">{{ $payment_provider_id }}</td>
                                                        @endif
                                                        <tr class="receipt-bold">
                                                            <td style="{{ $style['details'] }}" valign="top">Amount Paid</td>
                                                            <td class="receipt-figure" style="{{ $style['details'] }} font-weight: 600;"
                                                                valign="top" align="right">{{ $charged_amount }}</td>
                                                        </tr>
                                                        @if($tax_setting)
                                                            <tr class="receipt-bold">
                                                                <td style="{{ $style['details'] }}" valign="top">Including {{$tax_setting->name}} Tax</td>
                                                                <td class="receipt-figure" style="{{ $style['details'] }} font-weight: 600;"
                                                                    valign="top" align="right">{{ $tax_setting->rate }}%</td>
                                                            </tr>
                                                        @endif
                                                    </table>
                                                    @if($isHaveTemplateEmail && $footer)
                                                        {!! $footer !!}
                                                    @else
                                                        <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; Margin: 0; Margin-bottom: 15px;">
                                                            Notice something wrong?
                                                            <a href="mailto:{{ $business_email }}" target="_blank" style="color: #3498db; text-decoration: underline;">Contact our support team</a>
                                                            and we'll be happy to help.
                                                        </p>
                                                        <p style="{{ $style['help'] }}">ID: {{ $charge_id }}</p>
                                                    @endif

                                                    @isset($application)
                                                        <p style="{{ $style['help'] }}">Application Name: {{ $application['name'] }} AID: {{ $application['identifier'] }}</p>
                                                    @endisset
                                                </td>
                                                <td style="{{ $style['outer_td'] }}" valign="top">&nbsp;</td>
                                            </tr>
                                        </table>
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
