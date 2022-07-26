@extends('dashboard.business.payment-providers.method', [
    'title' => 'PayNow Settings'
])

@section('method-content')
    @if($businessBankAccount == null)
        <div class="paynow p-4">
            <h2 class="text-primary mb-3">
                Set up PayNow acceptance for your business
            </h2>

            <div class="alert border-top border-left-0 border-right-0 border-bottom-0 rounded-0 mb-0 alert-warning">
                Please add a bank account before you can enable PayNow.
                <a class="links" href="{{ route('dashboard.business.settings.bank-accounts.create-page', $business->getKey()) }}">Add Bank</a>
            </div>
        </div>
    @else
        <paynow-settings
            :provider="{{ json_encode($provider) }}"
            :success_message="{{ json_encode($success_message) }}"/>
    @endif
@endsection
