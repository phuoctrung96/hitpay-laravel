@php($title = __('Account Verification'))

@extends('layouts.business')

@section('business-content')
    <div class="row">
        <div class="col-md-12">
            @if($isOwner)
                <h4 class="title align-content-lg-center" id="title">Verification is <span id="status">Empty</span></h4>
                <p>Waiting complete the verification.</p>
            @else
                <h4 class="title align-content-lg-center" id="title">Business owner verification is pending.</h4>
                <p>Please ask the owner "<b>{{ $businessUserOwner->email }}</b>" to complete the verification</p>
            @endif
        </div>
    </div>
@endsection

@push('body-stack')
    <script src="https://cdn.cognitohq.com/flow.js"></script>
    <script>
        var isOwner = "{{ $isOwner }}";

        if (isOwner === '1') {
            var cognito = {};

            cognito.status = '';

            cognito.setPassedStatus = function() {
                if (cognito.status == 'passed') {
                    var theDiv = document.getElementById('status');
                    theDiv.innerHTML = "<b>Pending Approval</b>";
                }
            }

            var productionReady = '{{ $verificationProvider["production_ready"] }}';

            if (productionReady === '1') {
                var flowCognito = new Flow({
                    publishableKey: '{{ $verificationProvider["publishableKey"] ?? null }}',
                    templateId: '{{ $verificationProvider["templateId"] ?? null }}',
                    user: {
                        customerReference: '{{ $business->getKey() }}',
                        email: '{{ $business->email }}',
                        signature: '{{ $verificationProvider["customerSignature"] ?? null }}'
                    }
                });
            } else {
                var flowCognito = new Flow({
                    publishableKey: '{{ $verificationProvider["publishableKey"] ?? null }}',
                    templateId: '{{ $verificationProvider["templateId"] ?? null }}',
                    user: {
                        customerReference: '{{ $business->getKey() }}',
                        email: '{{ $business->email }}'
                    }
                });
            }

            flowCognito.open();

            flowCognito.on("all", event => {
                var topic = event.topic;

                if (topic == 'session') {
                    var action = event.action;

                    if (action == 'passed') {
                        cognito.status = 'passed';

                        cognito.setPassedStatus();

                        flowCognito.close();
                    }
                }
            });
        }
    </script>
@endpush
