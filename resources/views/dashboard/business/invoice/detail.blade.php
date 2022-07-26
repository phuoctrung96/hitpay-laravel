@php($title = 'Create New Invoice')

@extends('layouts.business', [
    'title' => $title,
])

@section('business-content')
    <div class="row">
        <div class="col-12 col-md-10 col-lg-10 mb-4">
            <div class="g-back-meta">
                <a class="btn-back" href="{{ route('dashboard.business.invoice.index', [
                    $business->getKey(),
                ]) }}">
                    <img src="{{asset('images/ico-back-normal.svg')}}"/> Back to Invoices
                </a>
            </div>
        </div>
        <div class="col-12 col-md-9 col-lg-9 main-content">
            <business-invoice-detail
            :currency_list="{{ json_encode($currencies) }}"
            :zero_decimal_list = "{{json_encode($zero_decimal_cur)}}"
            :business_logo = "'{{$business->logo ? $business->logo->getUrl() : asset('images/pdf-logo.png')}}'"
            ></business-invoice-detail>
        </div>
    </div>
@endsection

@push('body-stack')
    <script>
        window.Tax_Settings = @json($tax_settings);
        window.Invoice = @json($invoice ?? null);
        window.Customer = @json($customer ?? null);
        window.partialPayments = @json(isset($partialPayments) && count($partialPayments) ? $partialPayments : null);
    </script>
@endpush
