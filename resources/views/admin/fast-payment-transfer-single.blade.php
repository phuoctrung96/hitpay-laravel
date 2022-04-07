@extends('layouts.admin', [
    'title' => 'Fast Payment Transfer'
])

@section('admin-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 mb-4">
            <a href="{{ route('admin.transfer.fast-payment.index', [
                'status' => $transfer->status,
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to Payouts</a>
        </div>
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card shadow-sm mb-3">
                <div class="card-body p-4">
                    <label class="small text-uppercase text-muted mb-3">Transfer # {{ $transfer->getKey() }}</label>
                    <h2 class="text-primary mb-3 title">{{ $transfer->business->getName() }}</h2>
                    <span class="float-right">{{ getFormattedAmount($transfer->currency, $transfer->amount) }}</span>
                    @if ($transfer->remark)
                        <p class="text-dark mb-2">{{ $transfer->remark }}</p>
                    @endif
                    @switch ($transfer->status)
                        @case ('succeeded')
                            <p class="text-dark small mb-0">Transferred at {{ $transfer->updated_at->format('h:ia \o\n F d, Y (l)') }}</p>
                            @break
                        @case ('succeeded_manually')
                            <p class="text-dark small mb-0">Manual transferred at {{ $transfer->updated_at->format('h:ia \o\n F d, Y (l)') }}</p>
                            @break
                        @default
                            <p><span class="badge badge-warning">Pending</span></p>
                            <p>Failed Responses:</p>
                            @isset($transfer->data['requests'])
                                @php($requestsData = collect($transfer->data['requests']))
                                <ol class="text-monospace">
                                    @foreach ($requestsData->pluck('response.txnResponse')->where('txnStatus', 'RJCT') as $response)
                                        <li>{{ $response['txnSettlementDt'] ?? 'Unknown Time' }} - {{ $response['txnRejectCode'] ?? 'Unknown Code' }} - {{ $response['txnStatusDescription'] ?? 'Unknown Reason' }}</li>
                                    @endforeach
                                </ol>
                            @else
                                <p class="text-danger font-weight-bold {{ isset($transfer->data['activities']) ? '' : "mb-0"}}">No request found for pending transfer. Check backend.</p>
                            @endisset
                            <p>Additional Activities:</p>
                            @isset($transfer->data['activities'])
                                @php($activitiesData = collect($transfer->data['activities']))
                                <ol>
                                    @foreach ($activitiesData as $activity)
                                        <li><span class="badge badge-sm badge-info">{{ \Illuminate\Support\Facades\Date::make($activity['timestamp'])->toDateTimeString() }}</span> {{ $activity['message'] }}<br><p class="small mb-2"><small class="text-black-50">Message for developers: {{ $activity['exception']['message'] }}<br>Related file : {{ $activity['filename'] }}</small></p></li>
                                    @endforeach
                                </ol>
                            @else
                                <p class="text-danger font-weight-bold mb-0">No request found for pending transfer. Check backend.</p>
                            @endisset
                    @endswitch
                </div>
                @if ($successMessage = session('success_message'))
                    <div class="alert alert-success border-left-0 border-right-0 rounded-0 mb-0">
                        {{ $successMessage }}
                    </div>
                @endif
                @if ($transfer->status === 'request_pending')
                <form class="card-body {{ $successMessage ? '' : 'border-top' }}" action="{{ route('admin.transfer.fast-payment.update', $transfer->getKey()) }}" method="post">
                    @method('put')
                    @csrf
                    <input type="hidden" name="manual_transferred" value="1">
                    <div class="form-group mb-0">
                        <button type="submit" class="btn btn-primary"{{ $successMessage ? ' disabled' : '' }}>Marked as Manual Transfered</button>
                    </div>
                </form>
                <form class="card-body border-top" action="{{ route('admin.transfer.fast-payment.update', $transfer->getKey()) }}" method="post">
                    @method('put')
                    @csrf
                    @php([
                        $bank_swift_code,
                        $bank_account_no,
                    ] = explode('@', $transfer->payment_provider_account_id))
                    <div class="form-group">
                        <label for="bank_swift_code" class="small text-secondary">Select Bank</label>
                        <select id="bank_swift_code" class="custom-select bg-light{{ $errors->has('bank_swift_code') ? ' is-invalid' : '' }}" name="bank_swift_code">
                            @foreach ($bank_lists as $code => $name)
                                <option value="{{ $code }}" {{ old('bank_swift_code', $bank_swift_code) === $code ? 'selected' : '' }}>
                                    {{ $name }} ({{ $code }})
                                </option>
                            @endforeach
                        </select>
                        @error('bank_swift_code')
                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="bank_account_no" class="small text-secondary">Enter Bank Account No</label>
                        <input id="bank_account_no" name="bank_account_no" class="form-control{{ $errors->has('bank_account_no') ? ' is-invalid' : '' }}" autocomplete="off" value="{{ old('bank_account_no', $bank_account_no) }}" autofocus>
                        @error('bank_account_no')
                        <span class="invalid-feedback" role="alert">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group mb-0">
                        <button type="submit" class="btn btn-success"{{ $successMessage ? ' disabled' : '' }}>Update and Retry</button>
                    </div>
                </form>
                @endif
            </div>
        </div>
    </div>
@endsection
