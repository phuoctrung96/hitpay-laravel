@extends('layouts.business', [
    'title' => 'Create Invoices In Bulk',
])

@section('business-content')
    <div class="row">
        <div class="col-12 col-lg-8 mb-4">
            <a href="{{ route('dashboard.business.invoice.index', [
                $business->getKey(),
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to Invoices</a>
        </div>
        <div class="col-12 col-lg-8 main-content">
            <business-invoice-bulk></business-invoice-bulk>
        </div>
    </div>
@endsection

