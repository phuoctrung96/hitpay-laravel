@extends('layouts.business', [
    'title' => 'Create Invoices In Bulk',
])

@section('business-content')
    <div class="row">
        <div class="col-12 col-lg-8 mb-4">
            <a href="{{ route('dashboard.business.customer.index', [
                $business->getKey(),
            ]) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to Customers</a>
        </div>
        <div class="col-12 col-lg-8 main-content">
            <business-customer-bulk></business-customer-bulk>
        </div>
    </div>
@endsection

