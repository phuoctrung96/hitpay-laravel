@extends('layouts.business', [
    'title' => 'New Bank Account'
])

@section('business-content')
    <div class="row">
        <div class="col-12 col-md-9 col-lg-8 mb-4">
            <a href="{{ route('dashboard.business.settings.bank-accounts.homepage', [
                $business->getKey(),
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to Bank Accounts</a>
        </div>

        <div class="col-12 col-xl-9 mb-4">
            @if(session('error_message'))
                <div class="alert alert-warning border-left-0 border-right-0 rounded-0 alert-dismissible fade show"
                     role="alert">
                    {{ session('error_message') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif
        </div>

        <div class="col-12 col-xl-9 main-content">
            <business-settings-bank-accounts-create :business="{{ json_encode($business->toArray()) }}" :banks="{{ json_encode($banks) }}" :bank_accounts_count="{{ $business->bankAccounts->count() }}"></business-settings-bank-accounts-create>
        </div>
    </div>
@endsection
