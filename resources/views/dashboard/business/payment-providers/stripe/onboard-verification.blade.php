@extends('dashboard.business.payment-providers.method', [
    'title' => 'Onboard Verification'
])

@section('method-content')
    @if($type == 'company')
        <stripe-onboard-verification-company
            :provider="{{ json_encode($provider) }}"
            :type="{{ json_encode($type) }}"
            :account="{{ json_encode($account) }}"
            :persons="{{ json_encode($persons) }}"
            :countries="{{ json_encode($countries) }}"
            :document_company="{{ json_encode($documentCompany) }}"
        ></stripe-onboard-verification-company>
    @else
        <stripe-onboard-verification-individual
            :provider="{{ json_encode($provider) }}"
            :type="{{ json_encode($type) }}"
            :account="{{ json_encode($account) }}"
            :persons="{{ json_encode($persons) }}"
            :countries="{{ json_encode($countries) }}"
            :document_company="{{ json_encode($documentCompany) }}"
        ></stripe-onboard-verification-individual>
    @endif
@endsection
