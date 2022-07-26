@extends('layouts.business', [
    'title' => 'Bank Accounts'
])

@section('business-content')
    <div class="row">
        <div class="col-12 col-md-9 col-lg-8 mb-4">
            <a href="{{ route('dashboard.business.settings.bank-accounts.homepage', [
                $business->getKey(),
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to Bank Accounts</a>
        </div>
        <div class="col-12 col-xl-9 main-content">
            @if ($message = session('success_message'))
                <div class="alert alert-success alert-dismissible fade show">
                    <p class="mb-0">{{ $message }}</p>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif
            <business-settings-bank-accounts-edit
                :_bank_account="{{ json_encode($bankAccount->toArray()) }}"
                :banks="{{ json_encode($banks) }}"
                :bank_fields="{{ json_encode($bank_fields) }}">
            </business-settings-bank-accounts-edit>
        </div>
    </div>
@endsection
