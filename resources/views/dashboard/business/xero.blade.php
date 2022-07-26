@extends('layouts.business', [
    'title' => 'Products'
])

@section('business-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 main-content">
            <div class="btn-group btn-group-sm mb-3 w-100 shadow-sm">
            </div>
        </div>
    </div>
    @if(session('success_message') && isset($business->xero_refresh_token))
        <div class="alert alert-success border-left-0 border-right-0 rounded-0 alert-dismissible fade show" role="alert">
            {{ session('success_message') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    @if(session('failed_message') && !isset($business->xero_refresh_token))
        <div class="alert alert-danger border-left-0 border-right-0 rounded-0 alert-dismissible fade show" role="alert">
            {{ session('failed_message') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    <xero-account-settings></xero-account-settings>
    @push('body-stack')
        <script>
            window.xeroAccountTypes = @json(\App\Business\Xero::XERO_ACCOUT_TYPES);
            window.xeroInvoiceGrouping = @json(\App\Business\Xero::INVOICE_GROUPING_VARIANTS);
        </script>
    @endpush
@endsection
