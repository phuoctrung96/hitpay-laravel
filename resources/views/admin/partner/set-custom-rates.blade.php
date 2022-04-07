@extends('layouts.admin', [
    'title' => 'Charges'
])

@section('admin-content')
    <div class="row">
        <div class="col-md-9 col-lg-8 main-content">
            <div class="col-12 col-md-9 col-lg-8 mb-4">
                <a href="{{ route('admin.partner.show', $user->getKey()) }}"><i class="fas fa-reply fa-fw mr-3"></i> Back to Partner
                </a>
            </div>
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <label class="text-uppercase text-muted mb-0">{{ $user->display_name }}</label>
                    <h2 class="text-primary mb-3 title">Create Custom Rates</h2>
                </div>
                <form class="card-body bg-light p-4 border-top" method="post" action="{{ route('admin.partner.save-custom-rates', $user) }}">
                    @csrf
                    <div>
                        <h4>Stripe</h4>
                        <div class="form-row">
                            <div class="col-12 col-sm-6 mb-3">
                                <label for="channel" class="small text-secondary">Channel</label>
                                <select id="channel" class="custom-select{{ $errors->has('channel') ? ' is-invalid' : '' }}" name="channel[stripe]">
                                    @foreach (\App\Enumerations\Business\Channel::collection()->whereNotIn('key', [
                                        'LINK_SENT',
                                        'DEFAULT',
                                    ])->pluck('value')->toArray() as $name)
                                        <option value="{{ $name }}" {{ old('channel') === $name ? 'selected' : '' }}>
                                            {{ ucwords(str_replace('_', ' ', $name)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('channel')
                                <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 col-sm-6 mb-3">
                                <label for="method" class="small text-secondary">Method</label>
                                <select id="method" class="custom-select{{ $errors->has('method') ? ' is-invalid' : '' }}" name="method[stripe]">
                                    @foreach ([
                                        'card',
                                        'card_present',
                                        'wechat',
                                        'alipay',
                                        'grabpay',
                                        'others',
                                    ] as $code => $name)
                                        <option value="{{ $name }}" {{ old('method') === $name ? 'selected' : '' }}>
                                            {{ ucwords(str_replace('_', ' ', $name)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('method')
                                <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-12 col-sm-6 mb-3">
                                <label for="percentage" class="small text-secondary">Percentage</label>
                                <div class="input-group">
                                    <input id="percentage" name="percentage[stripe]" class="form-control{{ $errors->has('percentage') ? ' is-invalid' : '' }}" autocomplete="off" value="{{ old('percentage') }}" autofocus>
                                    <div class="input-group-append">
                                        <span class="input-group-text" id="basic-addon1">%</span>
                                    </div>
                                </div>
                                @error('percentage')
                                <span class="text-danger small mt-1" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 col-sm-6 mb-3">
                                <label for="fixed_amount" class="small text-secondary">Fixed Amount</label>
                                <input id="fixed_amount" name="fixed_amount[stripe]" class="form-control{{ $errors->has('fixed_amount') ? ' is-invalid' : '' }}" autocomplete="off" value="{{ old('fixed_amount') }}" autofocus>
                                @error('fixed_amount')
                                <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mt-5">
                        <h4>Paynow</h4>
                        <div class="form-row">
                            <div class="col-12 col-sm-6 mb-3">
                                <label for="channel" class="small text-secondary">Channel</label>
                                <select id="channel" class="custom-select{{ $errors->has('channel') ? ' is-invalid' : '' }}" name="channel[paynow]">
                                    @foreach (\App\Enumerations\Business\Channel::collection()->whereNotIn('key', [
                                        'LINK_SENT',
                                        'DEFAULT',
                                    ])->pluck('value')->toArray() as $name)
                                        <option value="{{ $name }}" {{ old('channel') === $name ? 'selected' : '' }}>
                                            {{ ucwords(str_replace('_', ' ', $name)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('channel')
                                <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 col-sm-6 mb-3">
                                <label for="method" class="small text-secondary">Method</label>
                                <select id="method" class="custom-select{{ $errors->has('method') ? ' is-invalid' : '' }}" name="method[paynow]">
                                    <option value="paynow_online" selected>
                                        PayNow Online
                                    </option>
                                    <option value="direct_debit" selected>
                                        Direct Debit
                                    </option>
                                </select>
                                @error('method')
                                <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-12 col-sm-6 mb-3">
                                <label for="percentage" class="small text-secondary">Percentage</label>
                                <div class="input-group">
                                    <input id="percentage" name="percentage[paynow]" class="form-control{{ $errors->has('percentage') ? ' is-invalid' : '' }}" autocomplete="off" value="{{ old('percentage') }}" autofocus>
                                    <div class="input-group-append">
                                        <span class="input-group-text" id="basic-addon1">%</span>
                                    </div>
                                </div>
                                @error('percentage')
                                <span class="text-danger small mt-1" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 col-sm-6 mb-3">
                                <label for="fixed_amount" class="small text-secondary">Fixed Amount</label>
                                <input id="fixed_amount" name="fixed_amount[paynow]" class="form-control{{ $errors->has('fixed_amount') ? ' is-invalid' : '' }}" autocomplete="off" value="{{ old('fixed_amount') }}" autofocus>
                                @error('fixed_amount')
                                <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>


                    <div class="form-group mb-0">
                        <button type="button" onclick="if(confirm('Do you really want to set custom rates for mapped merchants?')) $(this).closest('form').submit();" class="btn btn-success">Save</button>
                    </div>
                </form>
                <div class="card-body border-top pt-2">
                </div>
            </div>
        </div>
    </div>
@endsection
