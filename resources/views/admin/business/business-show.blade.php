@extends('layouts.admin', [
    'title' => $business->getName(),
])

@section('admin-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 main-content">
            <div class="col-12 col-md-9 col-lg-8 mb-4">
                <a href="{{ route('admin.business.index') }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to
                    Business</a>
            </div>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <h2 class="text-primary mb-3 title">{{ $business->getName() }}</h2>
                    <div class="media">
                        <div class="media-body">
                            <p class="text-dark small mb-2">
                                <span class="text-muted"># {{ $business->getKey() }}</span></p>
                            <p class="text-dark small mb-0">Login Email:
                                <span class="text-muted">{{ $business->owner->email }}</span></p>
                            <p class="text-dark small mb-0">Business Email:
                                <span class="text-muted">{{ $business->email }}</span></p>
                            <p class="text-dark small mb-0">WhatsApp Number:
                                <span class="text-muted">{{ $business->phone_number }}</span></p>
                            @if($business->website)
                                <p class="text-dark small mb-0">Website:
                                    <a href="https://{{$business->website}}" target="_blank">{{$business->website}}</a>
                                </p>
                            @endif
                            @if($business->merchant_category)
                                <p class="text-dark small mb-0">Merchant Category:
                                    <span class="text-muted">{{ \App\Business\BusinessCategory::getCategoryName($business->merchant_category) }}</span></p>
                            @endif
                            @if($business->referred_channel)
                                <p class="text-dark small mb-0">Referred Channel:
                                    <span class="text-muted">{{ $business->referred_channel }}</span></p>
                            @endif
                            <p class="text-dark small mb-0">Business Dashboard:
                                <a href="{{route('dashboard.business.home', $business->getKey())}}" target="_blank">Link</a></p>
                            <a class="small" href="{{ route('admin.business.edit', $business->getKey()) }}">Click here
                                to update business</a>
                        </div>
                    </div>
                    @if ($message = session('success'))
                        <div class="alert alert-success alert-dismissible fade show mt-3 mb-0" role="alert">
                            {{ $message }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
                </div>
                @if ($business->wallets->count())
                    <div class="card-body px-4 py-0">
                        <p class="font-weight-bold mb-4">Balances</p>
                    </div>
                    @php($wallets = $business->wallets->groupBy('currency'))
                    @foreach($wallets as $currency => $types)
                        <div class="card-body bg-light p-4 border-top">
                            <h5 class="font-weight-bold">{{ strtoupper($currency) }}</h5>
                            @foreach($types as $type)
                                <p class="text-monospace mb-0">
                                    {!! str_replace('~', "&nbsp;", str_pad(ucfirst($type->type), 10, '~')) !!}:
                                    @if ($type->balance < 0)
                                        <span
                                            class="text-danger">- {{ strtoupper($type->currency) }} {{ getFormattedAmount($type->currency, abs($type->balance), false) }}</span>
                                    @elseif ($type->balance > 0)
                                        <span
                                            class="text-success">{{ strtoupper($type->currency) }} {{ getFormattedAmount($type->currency, $type->balance, false) }}</span>
                                    @else
                                        {{ strtoupper($type->currency) }} {{ getFormattedAmount($type->currency, $type->balance, false) }}
                                    @endif
                                    @if ($type->type === \App\Enumerations\Business\Wallet\Type::DEPOSIT)
                                        <span
                                            class="small text-secondary">(Amount Set {{ strtoupper($type->currency) }} {{ getFormattedAmount($type->currency, $type->reserve_balance, false) }})</span>
                                    @endif
                                </p>
                            @endforeach
                            <div class="mt-3">
                                <a href="{{ route('admin.business.wallet', [$business->getKey(), $currency]) }}"
                                   class="btn btn-success btn-sm">View Transactions</a>
                            </div>
                        </div>
                    @endforeach
                    <div class="card-body border-top pt-2">
                    </div>
                @endif

                <div class="card-body px-4 py-0">
                    <p class="font-weight-bold mb-4">Compliance</p>
                </div>
                <div class="card-body bg-light p-4 border-top">
                    @if($business->verifications()->first())
                        <div class="form-group">
                            <label>Business Description</label>
                            <textarea name="" id="" cols="30" rows="5" class="form-control"
                                      disabled>{{$business->verifications()->first()->business_description}}</textarea>
                        </div>
                    @endif
                    <form action="{{route('admin.business.compliance', [$business->getKey()])}}" method="post">
                        @csrf
                        <div class="form-group">
                            <label for="risk_level">Risk Level</label>
                            <select id="risk_level" class="form-control" name="risk_level">
                                @foreach(\App\Enumerations\Business\ComplianceRiskLevel::getList() as $key => $value)
                                    <option value="{{$key}}"
                                            @if ($business->complianceNotes && $business->complianceNotes->risk_level == $key) selected @endif>{{$value}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="risk_level">Compliance Notes</label>
                            <textarea name="compliance_notes" id="" cols="10" rows="5"
                                      class="form-control">@if($business->complianceNotes){{$business->complianceNotes->compliance_notes}}@endif</textarea>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-success btn-sm">Save</button>
                        </div>
                    </form>
                </div>

                @if ($business->paymentProviders->count())
                    @php($currentBusinessUser = resolve(\App\Services\BusinessUserPermissionsService::class)->getBusinessUser(Auth::user(), $business))

                    <div class="card-body px-4 py-0">
                        <p class="font-weight-bold mb-4">Payment Providers</p>
                    </div>

                    <business-rates
                      :providers="{{ json_encode($business->paymentProviders) }}"
                      business_id="{{ $business->id }}"
                      currency="{{ $business->currency }}"
                      :allow_remove="{{ json_encode($currentBusinessUser->permissions['canRemoveStripeAccount']) }}"
                      :swift_codes="{{ json_encode(\App\Business\Transfer::$availableBankSwiftCodes) }}"></business-rates>
                @else
                    <div class="card-body px-4 py-3 border-top bg-danger">Payment Provider not set.</div>
                @endif
                <div class="card-body border-top pt-2">
                </div>
                @if ($business->gatewayProviders->count())
                    <div class="card-body px-4 py-0">
                        <p class="font-weight-bold mb-4">Integrations</p>
                    </div>
                    <div class="card-body bg-light border-top p-4">
                        @foreach ($business->gatewayProviders as $item)
                            <div class="media">
                                <div class="media-body">
                                    <p class="font-weight-bold mb-3">{{ strtoupper($item->name) }}</p>
                                    @php ($methodNames = [
                                        \App\Enumerations\Business\PaymentMethodType::PAYNOW   => 'PayNow',
                                        \App\Enumerations\Business\PaymentMethodType::CARD    => 'Visa, Mastercard and American Express (Including Apple Pay and Google Pay)',
                                        \App\Enumerations\Business\PaymentMethodType::WECHAT   => 'WeChatPay',
                                        \App\Enumerations\Business\PaymentMethodType::ALIPAY   => 'AliPay',
                                        \App\Enumerations\Business\PaymentMethodType::GRABPAY  => 'GrabPay',
                                        \App\Enumerations\Business\PaymentMethodType::SHOPEE  => 'Shopee Pay',
                                    ])
                                    @if (count($item->array_methods))
                                        <ul class="text-dark small mb-3">
                                            @foreach ($item->array_methods as $method)
                                                <li><span
                                                        class="text-muted">{{ $methodNames[$method] ?? $method }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="card-body px-4 py-3 border-top bg-danger">Integration not set.</div>
                @endif
                <div class="card-body border-top pt-2">
                </div>
                @if ($business->client->count())
                    <div class="card-body px-4 py-0">
                        <p class="font-weight-bold mb-4">OAuth Clients</p>
                    </div>
                    @foreach ($business->client as $client)
                        <div class="card-body bg-light p-4 border-top">
                            <p class="font-weight-bold mb-2">{{ $client->name }}</p>
                            <p class="small mb-0">Client ID : <span
                                    class="text-muted text-monospace">{{ $client->id }}</span></p>
                            <p class="small mb-0">Client Secret : <span
                                    class="text-muted text-monospace">{{ $client->secret }}</span></p>
                        </div>
                    @endforeach
                @else
                    <div class="card-body px-4 py-3 border-top bg-danger">OAuth client not set.</div>
                @endif
                <div class="card-body border-top pt-2">
                </div>
                @if ($business->apiKeys->count())
                    <div class="card-body px-4 py-0">
                        <p class="font-weight-bold mb-4">API Keys</p>
                    </div>
                    @foreach ($business->apiKeys as $apiKey)
                        <div class="card-body bg-light p-4 border-top">
                            <p class="small mb-0">API Key : <span
                                    class="text-muted text-monospace">{{ $apiKey->api_key }}</span></p>
                            <p class="small mb-0">Salt : <span
                                    class="text-muted text-monospace">{{ $apiKey->salt }}</span></p>
                            <p class="small mb-0">Status :
                                @if ($apiKey->is_enabled)
                                    <i class="fas fa-check text-success"></i>
                                @else
                                    <i class="fas fa-times text-danger"></i>
                                @endif
                            </p>
                        </div>
                    @endforeach
                @else
                    <div class="card-body px-4 py-3 border-top bg-danger">API key not set.</div>
                @endif
                <div class="card-body border-top pt-2">
                </div>
                <a class="hoverable" href="{{ route('admin.business.platform.index', $business->getKey()) }}">
                    <div class="card-body bg-light px-4 py-3 border-top">
                        <div class="media">
                            <i class="fas fa-cogs fa-fw text-body align-self-center mr-card mr-xs-4 mr-sm-5 mr-md-6"></i>
                            <div class="media-body align-self-center">
                                <span class="font-weight-bold d-inline-block">@lang('Platform')</span>
                            </div>
                        </div>
                    </div>
                </a>
                <a class="hoverable" href="{{ route('admin.business.charge.index', $business->getKey()) }}">
                    <div class="card-body bg-light px-4 py-3 border-top">

                        <div class="media">
                            <i class="fas fa-dollar-sign fa-fw text-body align-self-center mr-card mr-xs-4 mr-sm-5 mr-md-6"></i>
                            <div class="media-body align-self-center">
                                <span class="font-weight-bold d-inline-block">@lang('View Charges')</span>
                            </div>
                        </div>
                    </div>
                </a>
                <a class="hoverable"
                   href="{{ route('admin.business.transfer.fast-payment.index', $business->getKey()) }}">
                    <div class="card-body bg-light px-4 py-3 border-top">
                        <div class="media">
                            <i class="fas fa-list-alt fa-fw text-body align-self-center mr-card mr-xs-4 mr-sm-5 mr-md-6"></i>
                            <div class="media-body align-self-center">
                                <span class="font-weight-bold d-inline-block">@lang('View PayNowTransfer')</span>
                            </div>
                        </div>
                    </div>
                </a>
                <a class="hoverable" href="{{ route('admin.business.terminal.index', $business->getKey()) }}">
                    <div class="card-body bg-light px-4 py-3 border-top">
                        <div class="media">
                            <i class="fas fa-calculator fa-fw text-body align-self-center mr-card mr-xs-4 mr-sm-5 mr-md-6"></i>
                            <div class="media-body align-self-center">
                                <span class="font-weight-bold d-inline-block">@lang('View Terminals')</span>
                            </div>
                        </div>
                    </div>
                </a>
                <div class="card-body border-top pt-2"></div>
            </div>
            @if ($business->verified_wit_my_info_sg && ($verification = $business->verifications()->latest()->first()))
                <div class="card-body px-4 py-3 border-top">Verification Status: {{$verification->status}}</div>
                <div class="card-body border-top bg-light p-4">
                    <pre
                        class="mb-0">@if($verification->status === \App\Enumerations\VerificationStatus::VERIFIED){{json_encode($verification->my_info_data, 128)}}@else{{ json_encode($verification->submitted_data, 128) }}@endif</pre>
                </div>
            @else
                <div class="card-body px-4 py-3 border-top bg-danger">Business is not verified.</div>
            @endif
            @if (isset($verification) && $verification->supporting_documents)
                <div class="card-body px-4 py-0">
                    <p class="font-weight-bold mb-4">Verification documents</p>
                </div>
                <div class="card-body border-top bg-light p-4">
                    <ol>
                        @foreach(json_decode($verification->supporting_documents) as $doc)
                            <li>
                                <a href="{{route('admin.verification-files.download', str_replace('/', '_', $doc))}}">Download</a>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @else
                <div class="card-body px-4 py-3 border-top bg-danger">No verification documents provided</div>
            @endif
            @if (isset($verification) && $verification->supporting_documents)
                <div class="card-body px-4 py-0">
                    <p class="font-weight-bold mb-4">Verification documents</p>
                </div>
                <div class="card-body border-top bg-light p-4">
                    <ol>
                        @foreach(json_decode($verification->supporting_documents) as $doc)
                            <li>
                                <a href="{{route('admin.verification-files.download', str_replace('/', '_', $doc))}}">Download</a>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @else
                <div class="card-body px-4 py-3 border-top bg-danger">No verification documents provided</div>
            @endif
            @if (isset($verification) && $verification->identity_documents)
                <div class="card-body px-4 py-0">
                    <p class="font-weight-bold mb-4">Identity documents</p>
                </div>
                <div class="card-body border-top bg-light p-4">
                    <ol>
                        @foreach(json_decode($verification->identity_documents) as $key => $doc)
                            <li>
                                {{ucfirst($key)}} <a href="{{route('admin.verification-files.download', str_replace('/', '_', $doc))}}">Download</a>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @else
                <div class="card-body px-4 py-3 border-top bg-danger">No verification documents provided</div>
            @endif
            @if($business->verified_wit_my_info_sg && ($verification = $business->verifications()->where('status', 'pending')->first()))
                <form action="{{route('admin.business.verify', $business->id)}}" method="POST">
                    @csrf
                    @method('put')
                    <button class="btn btn-success mt-3 btn-block" type="submit">Verify</button>
                </form>
            @endif
            @if ($business->verified_wit_my_info_sg && ($verification = $business->verifications()->latest()->first()))
                <form action="{{route('admin.business.reject', $business->id)}}" method="POST">
                    @csrf
                    @method('put')
                    <button class="btn btn-danger mt-3 btn-block" type="submit">Reject</button>
                </form>
            @endif
            <button class="btn btn-danger mt-3 btn-block" data-toggle="modal"
                    data-target="#deleteBusinessModal">Delete business
            </button>
        </div>
    </div>
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold text-danger" id="deleteModalLabel">Warning!</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p id="warning-text">Are you sure you want to remove this rate?</p>
                    <form id="delete-form" method="post">
                        @csrf
                        @method('delete')
                        <button type="submit" class="btn btn-danger">Confirm</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="deleteBusinessModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel"
         aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold text-danger" id="deleteModalLabel">Warning!</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p id="warning-text">Do you want to delete just the business or delete the owner account as
                        well?</p>
                    <div class="d-flex justify-content-between">
                        <form action="{{route('admin.business.delete', [$business->id])}}" method="post">
                            @csrf
                            @method('delete')
                            <button type="submit" class="btn btn-danger">Delete business</button>
                        </form>
                        <form action="{{route('admin.business.delete', [$business->id, 'owner'])}}"
                              method="post">
                            @csrf
                            @method('delete')
                            <button type="submit" class="btn btn-danger">Delete business and owner</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('body-stack')
    @isset($stripe)
        <div class="modal fade" id="removeStripeAccountModal" tabindex="-1" role="dialog"
             aria-labelledby="removeStripeAccountModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger font-weight-bold" id="removeStripeAccountModalLabel">Remove
                            Stripe Account</h5>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to remove Stripe account for "{{ $business->getName() }}"?
                    </div>
                    <form class="modal-footer" method="post" action="{{ route('admin.business.payment-provider.stripe_sg.deauthorize', [
                        $business->getKey(),
                    ]) }}">
                        @csrf @method('delete')
                        <button type="submit" class="btn btn-danger">Confirm</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </form>
                </div>
            </div>
        </div>
    @endisset
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            $('#deleteModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var providerId = button.data('rate-id');
                var providerName = button.data('provider-name');
                var modal = $(this)

                modal.find('#warning-text').text('Are you sure you want to remove rate for \'' + providerName + '\'?')
                modal.find('#delete-form').prop('action', '{{ route('admin.business.rate.destroy', [
                    'business_id' => $business->getKey(),
                    'rate_id' => 'random_id',
                ]) }}'.replace('random_id', providerId));
            });
        });
    </script>
@endpush
