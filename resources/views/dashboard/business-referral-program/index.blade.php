@php($store_name = $business->getName())
@php($store_link = route('shop.business', $business->identifier ?? $business->getKey()))

@extends('layouts.business', [
    'title' => 'Welcome, ' . $store_name,
    'custom_title' => true
])

@section('business-title')
    Welcome, {{ $store_name }}
@endsection

@section('business-content')
    <div class="row">
        <div class="col-md-8 col-lg-8 main-content">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <h3 class="text-primary mb-0 title">Refer and Earn</h3>
                </div>
                <div class="card-body border-top p-4">
                    <div class="py-1">
                        <p style="font-size: 18px; color: #4a4a4a;">Know someone who can use HitPay?<br>
                            Invite them and earn <b>{{$business->businessReferral->referral_fee * 100}}%</b> of their
                            transactions volumes</p>
                        <div class="pt-2">
                            <div class="form-group">
                                <label class="small-text">Share your link</label>
                                <div class="input-group input-group-lg mb-3">
                                    <input id="referralUrl" readonly style="font-size: 12px; height: 48px;"
                                           value="{{route('register', ['referral_code' => $business->businessReferral->code])}}"
                                           class="form-control text-left text-monospace">

                                    <div class="input-group-append">
                                        <button id="amountLabel" class="input-group-text btn btn-primary pl-4 pr-4" onclick="copyUrl(this)">Copy</button>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center pt-4 pb-4">
                                <div style="border-top: 1px solid #979797; position: relative; top: 14px;"></div>
                                <span class=" pl-2 pr-2" style="display: inline-block; background-color: #fff; position: relative;">OR</span>
                            </div>

                            <div class="form-group">
                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                        <ul>
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                <form action="{{route('dashboard.business.referral-program.send-invite', $business)}}" method="post">
                                    {{csrf_field()}}
                                    <label class="small-text" for="email">Send Email</label>
                                    <div class="input-group input-group-lg mb-3">
                                        <input id="email" name="email" value="" type="email" style="font-size: 12px; height: 48px;"
                                               placeholder="Enter email address" required
                                               class="form-control text-left text-monospace">

                                        <div class="input-group-append">
                                            <button type="submit" id="amountLabel" class="input-group-text btn btn-primary pl-4 pr-4 text-monospace">Invite</button>
                                        </div>
                                    </div>
                                </form>
                                @if(session('success_message'))
                                    <div class="alert alert-success border-left-0 border-right-0 rounded-0 alert-dismissible fade show" role="alert">
                                        {{ session('success_message') }}
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <business-help-guide :page_type="'refer_and_earn'"></business-help-guide>
        </div>
        <div class="col-md-4 col-lg-4 main-content">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body py-5 text-center">
                    <div class="pl-4 pr-4 pb-0">
                        <div class="pt-3 pb-3 mb-3">
                            <p>You have earned</p>
                            <h1>{{strtoupper($business->currency)}}{{$amount}}</h1>
                        </div>
                        <div class="pb-3 mb-5">
                            <p>Businesses Referred</p>
                            <h1>{{$business->businessReferral->referredBusinesses()->count()}}</h1>
                        </div>
                        <a href="{{route('dashboard.business.balance.wallet', [$business, $business->currency, 'available', 'event' => 'business_referral_commission'])}}" class="btn btn-primary btn-lg btn-block">View Details</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('body-stack')
    <script>
        function copyUrl(button) {
            let input = document.querySelector('#referralUrl');
            input.select();

            try {
                let copied = document.execCommand('copy');
                button.innerHTML = "Copied";
                setTimeout(() => {
                    button.innerHTML = "Copy";
                }, 5000);
            } catch (err) {
                alert('Oops, unable to copy');
            }

            window.getSelection().removeAllRanges()
        }
    </script>
@endpush
