@extends('layouts.business', [
    'title' => 'Inventory Sync',
])

@php
if ($business->hotglueIntegration()->whereType('ecommerce')->whereConnected(true)->first()) {
    $business['hotglueIntegration'] = $business->hotglueIntegration()->with('jobInProgressJobCreated', 'jobInProgressJobQueued', 'jobDone', 'hotglueLocation')->whereType('ecommerce')->get();
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
    </script>
@endpush
