@extends('layouts.root', [
    'title' => $recurring_plan->name.' - '.$business->getName(),
])

@section('root-content')
    <body class="bg-light-primary">
    <div id="app">
        <div id="demo" class="alert alert-warning rounded-0 border-top-0 border-left-0 border-right-0 mb-0 d-none">
            <div class="container text-center small">
                <strong>Demo in test mode.</strong> This app is running in test mode. You will not be charged.
            </div>
        </div>
        <div class="container pt-4 pt-phone-5 pb-5">
            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-7 col-xl-6 order-1 order-md-2">
                    <div class="card shadow-sm mb-4 mb-phone-5">
                        <div class="card-body text-center py-4 border-bottom">
                            @isset ($business_logo)
                                <img src="{{ $business_logo }}" height="64" class="rounded mb-3"
                                     alt="{{ $business->getName() }}">
                            @else
                                <img src="{{ asset('hitpay/logo-000036.png') }}" class="mb-3" height="48"
                                     alt="{{ $app_name }}">
                            @endisset
                            <h2 class="h6 mb-3">{{ $business->getName() }}</h2>
                            <h1 class="h2 font-weight-bold mb-3">{{ $recurring_plan->name }}</h1>
                            @isset($recurring_plan->description)
                                <p class="small text-muted"># {{ $recurring_plan->getKey() }}</p>
                                <p class="small text-muted text-justify mb-0">{{ $recurring_plan->description }}</p>
                            @else
                                <p class="small text-muted mb-0"># {{ $recurring_plan->getKey() }}</p>
                            @endisset

                        </div>
                        <div class="card-body bg-light py-4 border-bottom">
                            <div class="form-group">
                                <label for="price" class="small text-muted text-uppercase">Customer Name</label>
                                <p class="form-control-plaintext mb-0">{{ $recurring_plan->customer_name ?? $recurring_plan->customer->name }}</p>
                            </div>
                            @if ($email = ($recurring_plan->customer_email ?? $recurring_plan->customer->email ?? false))
                                <div class="form-group">
                                    <label for="price" class="small text-muted text-uppercase">Customer Email</label>
                                    <p class="form-control-plaintext mb-0">{{ $email }}</p>
                                </div>
                            @endif
                            <div class="form-row">
                                <div class="col-12 col-phone-6 mb-3">
                                    <label class="small text-muted text-uppercase">Billing Cycle</label>
                                    <p class="form-control-plaintext mb-0">{{ ucfirst($recurring_plan->cycle) }}</p>
                                </div>
                                <div class="col-12 col-phone-6 mb-3">
                                    <label for="price" class="small text-muted text-uppercase">Due Today</label>
                                    <p class="form-control-plaintext mb-0">{{ $recurring_plan->getPrice() }}</p>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-12 col-phone-6 mb-3 mb-phone-0">
                                    <label
                                        class="small text-muted text-uppercase">{{ $recurring_plan->status === 'active' ? 'Next Charge Date' : 'Starts on' }}</label>
                                    <p class="form-control-plaintext mb-0">{{ $recurring_plan->expires_at->format('d F Y') }}</p>
                                </div>
                                <div class="col-12 col-phone-6 mb-phone-0">
                                    <label class="small text-muted text-uppercase">Status</label>
                                    <p class="form-control-plaintext mb-0">
                                        @switch($recurring_plan->status)
                                            @case('active')
                                            <span class="badge badge-lg badge-success">Active</span>
                                            @if ($recurring_plan->times_to_be_charged !== null)
                                                <br><span class="text-muted small mt-2">{{ $recurring_plan->times_charged }} / {{ $recurring_plan->times_to_be_charged }} charges made</span>
                                            @endif
                                            @break
                                            @case('completed')
                                            <span class="badge badge-lg badge-success">Completed</span>
                                            @if ($recurring_plan->times_to_be_charged !== null)
                                                <br><span class="text-muted small mt-2">{{ $recurring_plan->times_charged }} charges made</span>
                                            @endif
                                            @break
                                            @case('scheduled')
                                            <span class="badge badge-lg badge-warning">Pending</span>
                                            @if ($recurring_plan->times_to_be_charged !== null)
                                                <br><span class="text-muted small mt-2">{{ $recurring_plan->times_charged }} times to be charged</span>
                                            @endif
                                            @break
                                            @case('canceled')
                                            <span class="badge badge-lg badge-danger">Canceled</span>
                                            @break
                                            @default
                                            <span class="badge badge-lg badge-warning">Unknown</span>
                                        @endswitch
                                    </p>
                                </div>
                            </div>
                        </div>
                        @if ($recurring_plan->status !== 'active')
                            <div class="card-body py-4">
                                <ul class="nav nav-pills nav-justified" id="myTab" role="tablist">
                                    @if(in_array(\App\Enumerations\Business\PaymentMethodType::CARD, json_decode($recurring_plan->payment_methods)))
                                        <li class="nav-item">
                                            <a class="nav-link d-flex h-100 active" id="card-tab" data-toggle="tab"
                                               href="#card" role="tab" aria-controls="card" aria-selected="true">
                                                <span class="w-100 align-self-center">Card</span>
                                            </a>
                                        </li>
                                    @endif
                                    @if(in_array(\App\Enumerations\Business\PaymentMethodType::GIRO, json_decode($recurring_plan->payment_methods)))
                                        <li class="nav-item">
                                            <a class="nav-link d-flex h-100" id="direct-debit-tab" data-toggle="tab"
                                               href="#direct-debit" role="tab" aria-controls="direct-debit"
                                               aria-selected="false">
                                                <span class="w-100 align-self-center">GIRO Direct Debit<br><span
                                                        class="small">(DBS/POSB Only)</span></span>
                                            </a>
                                        </li>
                                    @endif

                                </ul>
                                <div class="tab-content mt-4" id="myTabContent">
                                    <div class="tab-pane fade show active" id="card" role="tabpanel"
                                         aria-labelledby="card-tab">
                                        <subscription></subscription>
                                    </div>
                                    <div class="tab-pane fade" id="direct-debit" role="tabpanel"
                                         aria-labelledby="direct-debit-tab">
                                        <p>Bill Reference: <span
                                                class="font-weight-bold">{{ $recurring_plan->dbs_dda_reference }}</span>
                                        </p>
                                        <p>Add HitPay as GIRO billing organisation by following steps below</p>
                                        <ol class="small text-muted mb-0">
                                            <li>Log in to your DBS/ POSB Internet Banking Account on the web</li>
                                            <li>Click on Pay > Add GIRO Arrangement</li>
                                            <li>Under Billing Organisation, Select HitPay</li>
                                            <li>Add <span
                                                    class="font-weight-bold">{{ $recurring_plan->dbs_dda_reference }}</span>
                                                as Bill Reference
                                            </li>
                                            <li>Enter 0 under Payment Limit</li>
                                            <li>Click on Next and hit Submit</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        @elseif ($recurring_plan->payment_provider === \App\Enumerations\PaymentProvider::DBS_SINGAPORE)
                            <div class="card-body py-4">
                                Bill Reference: <span
                                    class="font-weight-bold">{{ $recurring_plan->dbs_dda_reference }}</span>
                            </div>
                        @else
                            @if ($recurring_plan->status === 'active' && $recurring_plan->payment_provider_payment_method_id)
                                <div class="card-body pt-4 px-0 pb-0">
                                    <div class="small alert alert-info rounded-0 border-left-0 border-right-0 mb-0">
                                        <i class="fa fa-exclamation-triangle mr-1"></i> A card has been attached to this
                                        recurring plan, but you still can update the card.
                                    </div>
                                </div>
                            @endif
                            <div class="card-body py-4">
                                <subscription></subscription>
                            </div>
                        @endif
                    </div>
                    <p class="small text-center">
                        <a href="https://www.hitpayapp.com"><i class="fas fa-home"></i> @lang('Home')</a>
                    </p>
                    <ul class="list-inline small text-center mb-0">
                        <li class="list-inline-item">
                            <a href="https://www.hitpayapp.com/termsofservice">@lang('Terms of Service')</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <script src="https://js.stripe.com/v3/"></script>
    <script type="text/javascript" defer>
        window.Name = '{{ $recurring_plan->customer_name ?? $recurring_plan->customer->name }}';
        window.HasPaymentMethod = {{ $recurring_plan->status === 'active' && $recurring_plan->payment_provider_payment_method_id ? 'true' : 'false' }};
        window.StripePublishableKey = '{{ config('services.stripe.sg.key') }}';
    </script>
    </body>
@endsection
