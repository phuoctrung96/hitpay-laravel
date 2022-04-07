@extends('layouts.admin', [
    'title' => 'Create Campaign'
])

@section('admin-content')
    <div class="row">
        <div class="col-12 main-content">
            <div class="card border-0 shadow-sm">
                <admin-campaign-create-edit></admin-campaign-create-edit>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('body-stack')
    <script>
        window.PaymentMethods = @json($payment_methods);

        @isset($campaign)
            window.Campaign = @json($campaign)
            @else
            window.Campaign = null;
        @endisset
    </script>
@endpush
