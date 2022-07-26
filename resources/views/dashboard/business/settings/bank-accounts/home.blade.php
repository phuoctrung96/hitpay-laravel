@extends('layouts.business', [
    'title' => 'Bank Accounts'
])

@section('business-content')
    <div class="row">
        <div class="col-12 col-xl-9 main-content">
            <div class="card border-0 shadow-sm mb-3">
                @foreach ($bankAccounts->sortByDesc('id') as $bankAccount)
                    <div class="card-body p-4{{ !$loop->last ? ' border-bottom' : '' }}">
                        <div class="float-right">
                            <a class="small" href="{{ route('dashboard.business.settings.bank-accounts.edit-page', [
                                $bankAccount->business_id,
                                'b_bank_account' => $bankAccount->getKey(),
                            ]) }}"><i class="fa fa-pen fa-fw mr-2"></i> Edit</a>
                        </div>
                        <h6 class="font-weight-bold">{{ $bankAccount->bank_name === 'Unknown' ? $bankAccount->holder_name . '***' . substr($bankAccount->number, -4) : $bankAccount->bank_name }}</h6>
                        <p class="small mb-1 text-black-50"># {{ $bankAccount->getKey() }}</p>
                        <p class="mb-0">Account Number : ***{{ substr($bankAccount->number, -4) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <a class="btn btn-primary" href="{{ route('dashboard.business.settings.bank-accounts.create-page', [
        $business->getKey(),
    ]) }}">Add Bank Account</a>
@endsection
