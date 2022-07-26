@extends('layouts.admin', [
    'title' => 'Email Attachments',
    'app_classes' => [
        'content' => 'bg-light'
    ],
])

@push('head-stack')
    <style>
        .breadcrumb-item + .breadcrumb-item:before {
            content: "/";
            color: rgb(33, 37, 41);
        }

        .summary table thead tr {
            background-color: #dee2e6;
        }

        .summary table td {
            vertical-align: middle;
            white-space: nowrap !important;
        }

        .summary table td:first-child,
        .summary table tbody.breakdown td:first-child {
            width: 100% !important;
        }

        .summary table thead td {
            border-top: 0;
        }

        .summary table thead td:first-child {
            border-left: 1px solid #dee2e6;
        }

        .summary table thead td:last-child {
            border-right: 1px solid #dee2e6;
        }

        .summary table tbody + tbody {
            border-width: 1px;
        }

        .summary table tbody.breakdown.warning td {
            background-color: palegoldenrod;
            color: #333 !important;
        }

        .summary table tbody.breakdown.danger td {
            background-color: indianred;
            color: #FFF !important;
        }

        .summary table tbody.breakdown td {
            border-top: 0;
            background-color: #fafafa;
            font-size: 87.5%;
            padding-top: 12px;
            padding-bottom: 12px;
            color: #AAA;
        }

        .summary table tbody.breakdown tr:not(:only-child):first-child td {
            padding-bottom: 0;
        }

        .summary table tbody.breakdown tr:not(:only-child):last-child td {
            padding-top: 0;
        }

        .summary table tbody.breakdown tr:not(:first-child):not(:last-child) td {
            padding-top: 0;
            padding-bottom: 0;
        }

        .summary table tr td:first-child {
            padding-left: 24px;
        }

        .summary table tbody tr td:first-child {
            border-left: 1px solid #dee2e6;
        }

        .summary table tbody tr td:last-child {
            border-right: 1px solid #dee2e6;
        }

        .summary table tbody tr:last-child td {
            border-bottom: 1px solid #dee2e6;
        }

        .summary table tbody tr.danger {
            background-color: red;
        }

        .summary table tbody tr.danger td {
            text-align: center;
            font-weight: bold;
            border-left: 1px solid red;
            border-right: 1px solid red;
            border-bottom: 1px solid red;
        }

        .summary table tbody tr.warning {
            background-color: orange;
        }

        .summary table tbody tr.warning td {
            text-align: center;
            font-weight: bold;
            border-left: 1px solid orange;
            border-right: 1px solid orange;
            border-bottom: 1px solid orange;
        }

        .summary table td.number {
            text-align: right;
        }

        .summary table td:last-child {
            padding-right: 24px;
        }
    </style>
@endpush

@section('admin-content')
    <div class="container pt-4 pb-5">
        <div class="row">
            <div class="col-12 main-content">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-4">
                        @if (is_array($data))
                            <p class="text-muted mb-0">Reconciliations - Bank Statements</p>
                            <h2 class="text-primary font-weight-bold mb-3 title">Summary - {{ $date }}</h2>
                        @else
                            <p class="text-muted mb-0">Reconciliations</p>
                            <h2 class="text-primary font-weight-bold mb-3 title">Bank Statements</h2>
                        @endif
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a class="text-muted" href="{{ route('admin.reconciliations.bank-statements.index') }}">Root</a>
                                </li>
                                @foreach ($breadcrumb as $link)
                                    <li class="breadcrumb-item">
                                        @if ($loop->last)
                                            {{ $link['name'] }}
                                        @else
                                            <a class="text-muted" href="{{ $link['url'] }}">{{ $link['name'] }}</a>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        </nav>
                    </div>
                    @if (is_array($data))
                        <div class="card-body pt-0 pb-4 px-4">
                            <div class="table-responsive summary mb-3">
                                <table class="table small text-monospace border-top-0 mb-0">
                                    <thead class="table">
                                    <tr>
                                        <td>Description</td>
                                        <td class="number">Debit</td>
                                        <td class="number">Credit</td>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @php
                                        unset($remarks);

                                        if ($data['checked']['bank']['credit'] !== 0) {
                                            if ($data['checked']['bank']['debit'] !== 0) {
                                                $remarks[] = 'The debit and credit amount are not tally.';
                                            } else {
                                                $remarks[] = 'The credit amount is not tally.';
                                            }
                                        } elseif ($data['checked']['bank']['debit'] !== 0) {
                                            $remarks[] = 'The debit amount is not tally.';
                                        }

                                        if (isset($remarks)) {
                                            $remarks[] = 'This should not happen. Please check!!!';

                                            $remarks = join(' ', $remarks);
                                        }
                                    @endphp
                                    @isset($remarks)
                                        <tr class="danger text-white">
                                            <td colspan="3">{{ $remarks }}</td>
                                        </tr>
                                    @endisset
                                    <tr>
                                        <td>Bank Statements Summary - Total</td>
                                        <td class="number">{{ getFormattedAmount('sgd', $data['bank']['debit']['*']) }}</td>
                                        <td class="number">{{ getFormattedAmount('sgd', $data['bank']['credit']['*']) }}</td>
                                    </tr>
                                    </tbody>
                                    <tbody class="breakdown {{ isset($remarks) ? 'danger' : '' }}">
                                    <tr>
                                        <td>DICNP</td>
                                        <td class="number">-</td>
                                        <td class="number">{{ getFormattedAmount('sgd', $data['bank']['credit']['dicnps']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>DICNT</td>
                                        <td class="number">-</td>
                                        <td class="number">{{ getFormattedAmount('sgd', $data['bank']['credit']['dicnts']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>IRRFD</td>
                                        <td class="number">{{ getFormattedAmount('sgd', $data['bank']['debit']['irrfds']) }}</td>
                                        <td class="number">-</td>
                                    </tr>
                                    <tr>
                                        <td>IRGPP</td>
                                        <td class="number">{{ getFormattedAmount('sgd', $data['bank']['debit']['irgpps']) }}</td>
                                        <td class="number">-</td>
                                    </tr>
                                    <tr>
                                        <td>- Fee</td>
                                        <td class="number">{{ getFormattedAmount('sgd', $data['bank']['debit']['irgpp_fees']) }}</td>
                                        <td class="number">-</td>
                                    </tr>
                                    <tr>
                                        <td>Grab Pay</td>
                                        <td class="number">-</td>
                                        <td class="number">{{ getFormattedAmount('sgd', $data['bank']['credit']['grabpays']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>ShopeePay</td>
                                        <td class="number">-</td>
                                        <td class="number">{{ getFormattedAmount('sgd', $data['bank']['credit']['shopeepays']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Zip</td>
                                        <td class="number">-</td>
                                        <td class="number">{{ getFormattedAmount('sgd', $data['bank']['credit']['zips']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Unknown</td>
                                        <td class="number">
                                            @if ($data['bank']['debit']['unknown'] > 0)
                                                <span class="text-white font-weight-bold p-1 bg-warning">{{ getFormattedAmount('sgd', $data['bank']['debit']['unknown']) }}</span>
                                            @else
                                                {{ getFormattedAmount('sgd', $data['bank']['debit']['unknown']) }}
                                            @endif
                                        </td>
                                        <td class="number">
                                            @if ($data['bank']['credit']['unknown'] > 0)
                                                <span class="text-white font-weight-bold p-1 bg-warning">{{ getFormattedAmount('sgd', $data['bank']['credit']['unknown']) }}</span>
                                            @else
                                                {{ getFormattedAmount('sgd', $data['bank']['credit']['unknown']) }}
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="number">
                                            <span class="border-top border-bottom">{{ getFormattedAmount('sgd', $data['bank']['debit']['*']) }}</span>
                                        </td>
                                        <td class="number">
                                            <span class="border-top border-bottom">{{ getFormattedAmount('sgd', $data['bank']['credit']['*']) }}</span>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="table-responsive summary">
                                <table class="table small text-monospace border-top-0 mb-0">
                                    <thead class="table">
                                    <tr>
                                        <td>Description</td>
                                        <td class="number">Breakdown</td>
                                        <td class="number">Total</td>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    @php
                                        unset($remarks);

                                        if ($data['checked']['hitpay']['charges'] !== 0) {
                                            $remarks = 'The HitPay charges and the DICNP - Auto Refunds are not tally. This should not happen. Please check!!!';
                                        }
                                    @endphp
                                    @isset($remarks)
                                        <tr class="danger text-white">
                                            <td colspan="3">{{ $remarks }}</td>
                                        </tr>
                                    @endisset
                                    <tr>
                                        <td>HitPay - Charges</td>
                                        <td class="number"></td>
                                        <td class="number">{{ getFormattedAmount('sgd', $data['hitpay']['charges']) }}</td>
                                    </tr>
                                    </tbody>
                                    <tbody class="breakdown {{ isset($remarks) ? 'danger' : '' }}">
                                    <tr>
                                        <td>DICNP</td>
                                        <td class="number">{{ getFormattedAmount('sgd', $data['analysed']['charges']) }}</td>
                                        <td class="number"></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            - HitPay - Auto Refunds (Detected IRRFD - {{ getFormattedAmount('sgd', $data['analysed']['auto_refunds']) }})
                                        </td>
                                        <td class="number">{{ getFormattedAmount('sgd', $data['hitpay']['auto_refunds']) }}</td>
                                        <td class="number"></td>
                                        {{-- check if 0 and if detected IRFFD area matched --}}
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="number">
                                            <span class="border-top border-bottom">{{ getFormattedAmount('sgd', $data['hitpay']['charges']) }}</span>
                                        </td>
                                        <td class="number"></td>
                                    </tr>
                                    </tbody>
                                    <tbody>
                                    @php
                                        unset($remarks);

                                        if ($data['checked']['hitpay']['top_ups'] !== 0) {
                                            $remarks = 'The HitPay top ups and the DICNT are not tally. This should not happen. Please check!!!';
                                        }
                                    @endphp
                                    @isset($remarks)
                                        <tr class="danger text-white">
                                            <td colspan="3">{{ $remarks }}</td>
                                        </tr>
                                    @endisset
                                    <tr>
                                        <td>HitPay - Top Up</td>
                                        <td class="number"></td>
                                        <td class="number">{{ getFormattedAmount('sgd', $data['hitpay']['top_ups']) }}</td>
                                    </tr>
                                    </tbody>
                                    <tbody class="breakdown {{ isset($remarks) ? 'danger' : '' }}">
                                    <tr>
                                        <td>DICNT</td>
                                        <td class="number">{{ getFormattedAmount('sgd', $data['analysed']['top_ups']) }}</td>
                                        <td class="number"></td>
                                    </tr>
                                    </tbody>
                                    <tbody>
                                    @php
                                        unset($remarks);

                                        if ($data['checked']['hitpay']['cashback'] !== 0) {
                                            $remarks = 'The HitPay cashback and the detected IRRFD are not tally. This should not happen. Please check!!!';
                                        }
                                    @endphp
                                    @isset($remarks)
                                        <tr class="danger text-white">
                                            <td colspan="3">{{ $remarks }}</td>
                                        </tr>
                                    @endisset
                                    <tr>
                                        <td>HitPay - Cashback</td>
                                        <td class="number"></td>
                                        <td class="number">{{ getFormattedAmount('sgd', $data['hitpay']['cashback']) }}</td>
                                    </tr>
                                    </tbody>
                                    <tbody class="breakdown {{ isset($remarks) ? 'danger' : '' }}">
                                    <tr>
                                        <td>Detected - IRRFD for Cashback</td>
                                        <td class="number">{{ getFormattedAmount('sgd', $data['analysed']['cashback']) }}</td>
                                        <td class="number"></td>
                                    </tr>
                                    </tbody>
                                    <tbody>
                                    @php
                                        unset($remarks);

                                        if ($data['checked']['hitpay']['campaign_cashback'] !== 0) {
                                            $remarks = 'The HitPay campaign cashback and the detected IRRFD are not tally. This should not happen. Please check!!!';
                                        }
                                    @endphp
                                    @isset($remarks)
                                        <tr class="danger text-white">
                                            <td colspan="3">{{ $remarks }}</td>
                                        </tr>
                                    @endisset
                                    <tr>
                                        <td>HitPay - Campaign Cashback</td>
                                        <td class="number"></td>
                                        <td class="number">{{ getFormattedAmount('sgd', $data['hitpay']['campaign_cashback']) }}</td>
                                    </tr>
                                    </tbody>
                                    <tbody class="breakdown {{ isset($remarks) ? 'danger' : '' }}">
                                    <tr>
                                        <td>Detected - IRRFD for Campaign Cashback</td>
                                        <td class="number">{{ getFormattedAmount('sgd', $data['analysed']['campaign_cashback']) }}</td>
                                        <td class="number"></td>
                                    </tr>
                                    </tbody>
                                    <tbody>
                                    @php
                                        unset($remarks);

                                        if ($data['checked']['hitpay']['refunds'] !== 0) {
                                            $remarks = 'The HitPay refunds and the detected IRRFD are not tally. This should not happen. Please check!!!';
                                        }
                                    @endphp
                                    @isset($remarks)
                                        <tr class="danger text-white">
                                            <td colspan="3">{{ $remarks }}</td>
                                        </tr>
                                    @endisset
                                    <tr>
                                        <td>HitPay - Refunds</td>
                                        <td class="number"></td>
                                        <td class="number">{{ getFormattedAmount('sgd', $data['hitpay']['refunds']) }}</td>
                                    </tr>
                                    </tbody>
                                    <tbody class="breakdown {{ isset($remarks) ? 'danger' : '' }}">
                                    <tr>
                                        <td>Detected - IRRFD for Refunds</td>
                                        <td class="number">{{ getFormattedAmount('sgd', $data['analysed']['refunds']) }}</td>
                                        <td class="number"></td>
                                    </tr>
                                    </tbody>
                                    <tbody>
                                    @php
                                        unset($remarks);

                                        $suspected = $data['checked']['suspected']['irgpps_reverted'] === 0;

                                        if ($data['checked']['hitpay']['transfers'] !== 0) {
                                            if ($suspected) {
                                                $remarks = 'The HitPay payouts and the detected IRGPP are not tally. Seems like there are some reverted.';
                                            } else {
                                                $remarks = 'The HitPay payouts and the detected IRGPP are not tally. This should not happen. Please check!!!';
                                            }
                                        }
                                    @endphp
                                    @isset($remarks)
                                        <tr class="{{ $suspected ? 'warning' : 'danger' }} text-white">
                                            <td colspan="3">{{ $remarks }}</td>
                                        </tr>
                                    @endisset
                                    <tr>
                                        <td>HitPay - Payouts</td>
                                        <td class="number"></td>
                                        <td class="number">{{ getFormattedAmount('sgd', $data['hitpay']['transfers']) }}</td>
                                    </tr>
                                    </tbody>
                                    <tbody class="breakdown {{ isset($remarks) ? $suspected ? 'warning' : 'danger' : '' }}">
                                    <tr>
                                        <td>IRGPP</td>
                                        <td class="number">{{ getFormattedAmount('sgd', $data['analysed']['transfers']) }}</td>
                                        <td class="number"></td>
                                    </tr>
                                    <tr>
                                        <td>- IRGPP - Suspected Reverts</td>
                                        <td class="number">{{ getFormattedAmount('sgd', $data['suspected']['irgpps_reverted']) }}</td>
                                        <td class="number"></td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="number">
                                            <span class="border-top border-bottom">{{ getFormattedAmount('sgd', $data['hitpay']['transfers']) }}</span>
                                        </td>
                                        <td class="number"></td>
                                    </tr>
                                    </tbody>
                                    <tbody>
                                    @php
                                        unset($remarks);

                                        if ($data['checked']['hitpay']['commissions'] !== 0) {
                                            $remarks = 'The HitPay top ups and the DICNT are not tally. This should not happen. Please check!!!';
                                        }
                                    @endphp
                                    @isset($remarks)
                                        <tr class="danger text-white">
                                            <td colspan="3">{{ $remarks }}</td>
                                        </tr>
                                    @endisset
                                    <tr>
                                        <td>HitPay - Commissions</td>
                                        <td class="number"></td>
                                        <td class="number">{{ getFormattedAmount('sgd', $data['hitpay']['commissions']) }}</td>
                                    </tr>
                                    </tbody>
                                    <tbody class="breakdown {{ isset($remarks) ? 'danger' : '' }}">
                                    <tr>
                                        <td>IRRGP</td>
                                        <td class="number">{{ getFormattedAmount('sgd', $data['analysed']['commissions']) }}</td>
                                        <td class="number"></td>
                                    </tr>
                                    </tbody>
                                    <tbody>
                                    @php
                                        unset($remarks);

                                        if ($data['analysed']['payment_intents'] !== 0) {
                                            $remarks = 'The HitPay top ups and the DICNT are not tally. This should not happen. Please check!!!';
                                        }
                                    @endphp
                                    @isset($remarks)
                                        <tr class="danger text-white">
                                            <td colspan="3">{{ $remarks }}</td>
                                        </tr>
                                    @endisset
                                    <tr>
                                        <td>HitPay - Payment Intents</td>
                                        <td class="number"></td>
                                        <td class="number">{{ getFormattedAmount('sgd', $data['analysed']['payment_intents']) }}</td>
                                        {{-- if more than 0 then wrong --}}
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                    @if (is_string($data))
                        <div class="alert alert-danger small rounded-0 border-left-0 border-right-0 px-4 py-2 mb-1">{{ $data }}</div>
                    @endif
                    <div>
                        @if($parent)
                            <a href="{{ $parent }}">
                                <div class="card-body bg-light border-top px-4 py-2 text-monospace">
                                    <i class="fas fa-fw fa-reply"></i> ...
                                </div>
                            </a>
                        @endif
                        @foreach ($folders as $folder)
                            <a href="{{ $folder['url'] }}">
                                <div class="card-body bg-light border-top px-4 py-2 text-monospace">
                                    <i class="far fa-fw fa-folder"></i> {{ $folder['name'] }}
                                </div>
                            </a>
                        @endforeach
                        @foreach ($files as $file)

                                <div class="card-body bg-light border-top px-4 py-2 text-monospace">
                                    <i class="fa-fw {{ $file['type'] ?? 'far fa-file' }}"></i> {{ $file['name'] }}
                                    <a class="float-right" href="{{ $file['url'] }}" target="_blank"><i class="fas fa-download"></i></a>
                                </div>
                        @endforeach
                    </div>
                    <div class="card-body border-top p-4">
                    </div>
                </div>
            </div>
        </div>
@endsection
