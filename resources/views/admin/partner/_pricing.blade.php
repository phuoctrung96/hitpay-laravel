<div style="margin-left: -25px; margin-right: -25px" class="mt-5">
    <form class="card-body bg-light pt-4 pb-4 border-top" method="post" action="{{ route('admin.partner.save-pricing', $user) }}">
        @csrf
        <div class="pricing-container">
            @foreach($partner->pricingItems as $i => $pricing)
                <div class="pricing-fields pb-2 mb-4" style="border-bottom: 1px solid #ccc;" data-index="{{$i}}">
                    <div>
                        <h4>Stripe</h4>
                        <div class="form-row">
                            <div class="col-12 col-sm-6 mb-3">
                                <label for="channel" class="small text-secondary">Channel</label>
                                <select id="channel" class="custom-select{{ $errors->has('stripe_channel') ? ' is-invalid' : '' }}" name="pricing[{{$i}}][stripe_channel]">
                                    @foreach (\App\Enumerations\Business\Channel::collection()->whereNotIn('key', [
                                        'LINK_SENT',
                                        'DEFAULT',
                                    ])->pluck('value')->toArray() as $name)
                                        <option value="{{ $name }}" {{ $pricing['stripe_channel'] === $name ? 'selected' : '' }}>
                                            {{ ucwords(str_replace('_', ' ', $name)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('stripe_channel')
                                <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 col-sm-6 mb-3">
                                <label for="method" class="small text-secondary">Method</label>
                                <select id="method" class="custom-select{{ $errors->has('stripe_method') ? ' is-invalid' : '' }}" name="pricing[{{$i}}][stripe_method]">
                                    @foreach ([
                                        'card',
                                        'card_present',
                                        'wechat',
                                        'alipay',
                                        'grabpay',
                                        'others',
                                    ] as $code => $name)
                                        <option value="{{ $name }}" {{ old('method', $pricing['stripe_method']) === $name ? 'selected' : '' }}>
                                            {{ ucwords(str_replace('_', ' ', $name)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('stripe_method')
                                <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-12 col-sm-6 mb-3">
                                <label for="percentage" class="small text-secondary">Percentage</label>
                                <div class="input-group">
                                    <input id="percentage" name="pricing[{{$i}}][stripe_percentage]" class="form-control{{ $errors->has('stripe_percentage') ? ' is-invalid' : '' }}"
                                           autocomplete="off" value="{{ old('percentage', $pricing['stripe_percentage']) }}" autofocus>
                                    <div class="input-group-append">
                                        <span class="input-group-text" id="basic-addon1">%</span>
                                    </div>
                                </div>
                                @error('stripe_percentage')
                                <span class="text-danger small mt-1" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 col-sm-6 mb-3">
                                <label for="fixed_amount" class="small text-secondary">Fixed Amount</label>
                                <input id="fixed_amount" name="pricing[{{$i}}][stripe_fixed_amount]" class="form-control{{ $errors->has('stripe_fixed_amount') ? ' is-invalid' : '' }}"
                                       autocomplete="off" value="{{ old('stripe_fixed_amount', $pricing['stripe_fixed_amount']) }}" autofocus>
                                @error('stripe_fixed_amount')
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
                                <select id="channel" class="custom-select{{ $errors->has('paynow_channel') ? ' is-invalid' : '' }}" name="pricing[{{$i}}][paynow_channel]">
                                    @foreach (\App\Enumerations\Business\Channel::collection()->whereNotIn('key', [
                                        'LINK_SENT',
                                        'DEFAULT',
                                    ])->pluck('value')->toArray() as $name)
                                        <option value="{{ $name }}" {{ old('paynow_channel', $pricing['paynow_channel']) === $name ? 'selected' : '' }}>
                                            {{ ucwords(str_replace('_', ' ', $name)) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('paynow_channel')
                                <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 col-sm-6 mb-3">
                                <label for="method" class="small text-secondary">Method</label>
                                <select id="method" class="custom-select{{ $errors->has('paynow_method') ? ' is-invalid' : '' }}" name="pricing[{{$i}}][paynow_method]">
                                    @foreach(['paynow_online' => 'PayNow Online', 'direct_debit' => 'Direct Debit'] as $key => $value)
                                        <option value="{{$key}}"  {{ old('paynow_method', $pricing['paynow_method']) === $name ? 'selected' : '' }}>
                                            {{$value}}
                                        </option>
                                    @endforeach
                                </select>
                                @error('paynow_method')
                                <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-12 col-sm-6 mb-3">
                                <label for="percentage" class="small text-secondary">Percentage</label>
                                <div class="input-group">
                                    <input id="percentage" name="pricing[{{$i}}][paynow_percentage]" class="form-control{{ $errors->has('paynow_percentage') ? ' is-invalid' : '' }}"
                                           autocomplete="off" value="{{ old('paynow_percentage', $pricing['paynow_percentage']) }}" autofocus>
                                    <div class="input-group-append">
                                        <span class="input-group-text" id="basic-addon1">%</span>
                                    </div>
                                </div>
                                @error('paynow_percentage')
                                <span class="text-danger small mt-1" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-12 col-sm-6 mb-3">
                                <label for="fixed_amount" class="small text-secondary">Fixed Amount</label>
                                <input id="fixed_amount" name="pricing[{{$i}}][paynow_fixed_amount]" class="form-control{{ $errors->has('paynow_fixed_amount') ? ' is-invalid' : '' }}"
                                       autocomplete="off" value="{{ old('paynow_fixed_amount', $pricing['paynow_fixed_amount']) }}" autofocus>
                                @error('paynow_fixed_amount')
                                <span class="invalid-feedback" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>


        <div class="form-group mb-0">
            <button type="submit" class="btn btn-primary">Save</button>
            <button class="btn btn-link add-pricing ml-3" type="button">Add</button>
        </div>
    </form>
</div>
@push('body-stack')
    <script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ=" crossorigin="anonymous"></script>
    <script type="text/javascript">
        $(document).ready(function () {
            $('.add-pricing').on('click', function () {
                let fields = $('.pricing-fields:first').clone();
                let index = $('.pricing-fields').length;
                fields.find('input').val('');
                fields.find('input').each(function () {
                    $(this).attr('name', $(this).attr('name').replace(/pricing\[\d+\]/, 'pricing['+index+']'));
                });

                fields.find('select').each(function () {
                    $(this).attr('name', $(this).attr('name').replace(/pricing\[\d+\]/, 'pricing['+index+']'));
                    $(this).find('option').removeAttr('selected');
                    $(this).find('option:first').attr('selected', 'selected');
                })
                $('.pricing-container').append(fields)
            });
        });
    </script>
@endpush
