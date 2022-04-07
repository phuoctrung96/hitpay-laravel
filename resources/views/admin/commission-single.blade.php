@extends('layouts.admin', [
    'title' => 'Commissions'
])

@section('admin-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 mb-4">
            <a href="{{ route('admin.commission.index', [
                'status' => $commission->status,
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to Commissions</a>
        </div>
        <div class="col-md-9 col-lg-8 main-content">
            <div class="card shadow-sm mb-3">
                <div class="card-body p-4">
                    <label class="small text-uppercase text-muted mb-3">Commission # {{ $commission->getKey() }}</label>
                    <h2 class="text-primary mb-3 title">{{ $commission->business->getName() }}</h2>
                    <span class="float-right">{{ getFormattedAmount($commission->currency, $commission->amount) }}</span>
                    @if ($commission->remark)
                        <p class="text-dark mb-2">{{ $commission->remark }}</p>
                    @endif
                    @switch ($commission->status)
                        @case ('succeeded')
                            <p class="text-dark small mb-0">Transferred at {{ $commission->updated_at->format('h:ia \o\n F d, Y (l)') }}</p>
                            @break
                        @case ('succeeded_manually')
                            <p class="text-dark small mb-0">Manual transferred at {{ $commission->updated_at->format('h:ia \o\n F d, Y (l)') }}</p>
                            @break
                        @default
                            <span class="badge badge-warning">Pending</span>
                    @endswitch
                </div>
                @if ($successMessage = session('success_message'))
                    <div class="alert alert-success border-left-0 border-right-0 rounded-0 mb-0">
                        {{ $successMessage }}
                    </div>
                @endif
                @if ($commission->status === 'request_pending')
                <form class="card-body {{ $successMessage ? '' : 'border-top' }}" action="{{ route('admin.commission.update', $commission->getKey()) }}" method="post">
                    @method('put')
                    @csrf
                    <input type="hidden" name="manual_transferred" value="1">
                    <div class="form-group mb-0">
                        <button type="submit" class="btn btn-primary"{{ $successMessage ? ' disabled' : '' }}>Marked as Manual Transfered</button>
                    </div>
                </form>
                <form class="card-body border-top" action="{{ route('admin.commission.update', $commission->getKey()) }}" method="post">
                    @method('put')
                    @csrf
                    @php($accountDetails = explode('@', $commission->payment_provider_account_id))
                    <div class="form-group">
                        <label for="bank_swift_code" class="small text-secondary">Select Bank</label>
                        <select id="bank_swift_code" class="custom-select bg-light{{ $errors->has('bank_swift_code') ? ' is-invalid' : '' }}" name="bank_swift_code">
                            @foreach ($bank_lists as $code => $name)
                                <option value="{{ $code }}" {{ old('bank_swift_code', $accountDetails[0] ?? null) === $code ? 'selected' : '' }}>
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
                        <input id="bank_account_no" name="bank_account_no" class="form-control{{ $errors->has('bank_account_no') ? ' is-invalid' : '' }}" autocomplete="off" value="{{ old('bank_account_no', $accountDetails[1] ?? null) }}" autofocus>
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
