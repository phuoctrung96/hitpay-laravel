@push('head-stack')
    <style>
        .dashboard-card {
            border-radius: 6px;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, .2);
            padding: 24px 32px;
            min-width: 300px;
        }

        .today-title {
            font-size: 18px;
            font-weight: 500;
            color: #4A4A4A;
        }

        .today-item {
            line-height: 1.16;
            margin-right: 32px;
        }
        .ti-title {
            font-size: 14px;
            color: #9B9B9B;
        }
    </style>
@endpush

@extends('layouts.business', [
    'title' => 'Welcome, ' . $storeName,
    'custom_title' => true
])

@section('business-title')
  Welcome, {{ $storeName }}
@endsection

@section('business-content')
    @if($business->business_type === 'partner')
        @php($partner = Auth::user()->load('businessUsers')->toArray()['business_partner'])
        <div class="d-flex flex-column flex-xl-row dash-row">
            <div class="dashboard-card d-flex flex-column flex-grow-1" style="height: auto; min-height: 211px;">
                <div class="today-title">
                    Overview
                </div>

                <div class="flex-grow-1 align-items-center flex-wrap mb-0 justify-content-between">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="today-item mt-2">
                                <span class="ti-title">Referral code</span>
                                <div>
                                    <span>{{ $partner['referral_code'] }}</span>
                                </div>
                            </div>
                            <div class="today-item mt-2">
                                <span class="ti-title">Referral URL</span>
                                <div>
                                    <span>{{ $partner['referral_url'] }}</span>
                                </div>
                            </div>
                            <div class="today-item mt-2">
                                <span class="ti-title">Website</span>
                                <div>
                                    <span>{{ $partner['website'] }}</span>
                                </div>
                            </div>
                            <div class="today-item mt-2">
                                <span class="ti-title">Platforms</span>
                                <div>
                                    <span>{{ join(', ', $partner['platforms']) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="today-item mt-2">
                                <span class="ti-title">Services</span>
                                <div>
                                    <span>{{ join(', ', $partner['services']) }}</span>
                                </div>
                            </div>
                            <div class="today-item mt-2">
                                <span class="ti-title">Description</span>
                                <div>
                                    <span>{{ $partner['short_description'] }}</span>
                                </div>
                            </div>
                            <div class="today-item mt-2">
                                <span class="ti-title">Special sign up offer to HitPay Merchants</span>
                                <div>
                                    <span>{{ $partner['special_offer'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 text-right">
                            <img src="/storage/{{$partner['logo_path']}}" style="height: 120px" alt=""/>
                        </div>
                    </div>
                </div>
            </div>

            @if($partner['status'] == 'pending')
            <div class="dashboard-card d-flex flex-column flex-grow-1 ml-3" style="height: auto; min-height: 211px;" >
                <div class="today-title mt-5 pt-5">
                    Your partner account has been submitted for approval
                </div>
            </div>
            @endif
        </div>
    @elseif(count($dailyData['lastTransactions']) > 0)
        <main-dashboard
            business_id="{{ $business->getKey() }}"
            :business="{{ json_encode($business) }}"
            :is_show_modal_verification="{{ json_encode($isShowModalVerification) }}"
            :country_code="{{ json_encode($business->country) }}"
            :daily_data="{{ json_encode($dailyData) }}"
            :user="{{json_encode(Auth::user()->load('businessUsers'))}}"></main-dashboard>
            <business-help-guide :page_type="'overview'"></business-help-guide>
    @else
        <getting-started
            :payment_count="{{json_encode($isPayment)}}"
            :is_show_modal_verification="{{json_encode($isShowModalVerification)}}"
            :is_verification_verified="{{ json_encode($isVerificationVerified) }}"
            :business="{{ json_encode($business) }}"
            business_id="{{ $business->getKey() }}"
            :country_code="{{ json_encode($business->country) }}"
        />
    @endif

@endsection

@push('body-stack')
    @if($business->country !== "sg" && $isShowModalVerification)
        <script src="https://cdn.cognitohq.com/flow.js"></script>

        <script>
            var verificationProviderActive = "{{ (bool)count($verificationProvider) }}";

            var isOwner = "{{ $isOwner }}";

            if (verificationProviderActive == '1' && isOwner == '1') {
                var cognito = {};

                cognito.status = '';

                var productionReady = '{{ isset($verificationProvider["production_ready"]) ?? '0' }}';

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

                            flowCognito.close();
                        }
                    }
                });
            }
        </script>
    @endif
    <input id="store-url" class="d-none" value="{{ $storeLink }}" title="Store Link" disabled>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let copyButton = document.getElementById('copyButton');

            if (copyButton) {
                copyButton.addEventListener('click', function () {
                    event.preventDefault();

                    target = document.getElementById('store-url');

                    target.classList.remove('d-none');
                    target.removeAttribute('disabled');

                    var currentFocus = document.activeElement;

                    target.focus();
                    target.setSelectionRange(0, target.value.length);

                    var succeed;

                    try {
                        succeed = document.execCommand('copy');

                        alert('Store Link Copied');
                    } catch (e) {
                        succeed = false;
                    }

                    if (currentFocus && typeof currentFocus.focus === 'function') {
                        currentFocus.focus();
                    }

                    target.setAttribute('disabled', true);
                    target.classList.add('d-none');

                    return succeed;
                });
            }
        });
    </script>
@endpush
