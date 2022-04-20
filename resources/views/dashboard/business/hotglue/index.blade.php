@extends('layouts.business', [
    'title' => 'Inventory Sync',
])

@php
if ($business->hotglueIntegration()->whereType('ecommerce')->whereConnected(true)->first()) {
    $business['hotglueIntegration'] = $business->hotglueIntegration()->with('jobInProgress')->get();
} else {
    $business['hotglueIntegration'] = [];
}

$hotglueConfigs = config('services.hotglue');
unset($hotglueConfigs['secret_api_key']);
@endphp

@push('head-script')
    <script src="https://hotglue.xyz/widget.js"></script>
@endpush

@section('business-content')
    <business-hotglue :hotglue_configs="{{ json_encode($hotglueConfigs) }}"></business-hotglue>
@endsection

@push('body-stack')
    <script>
        window.HotGlue = HotGlue;
        window.Business = @json($business);
    </script>
@endpush