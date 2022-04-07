@php($hasRemark = strlen($product->description) > 0)
@php($shippableCountries = $shippableCountry->count())
@php($globalShippable = isset($shippableCountry['GLOBAL']) && count($shippableCountry['GLOBAL']))
@php($canPickup = isset($account->extra_data['store_address']) && $product->is_pickup_allowed)

@extends('checkout.layouts.app', [
    'page_title' => $product->name . ' - '.($account->display_name ?? $account->name),
])

@push('head')
    <meta name="stripe-publishable-key" content="{{ config('services.stripe.'.$business->payment_provider.'.key') }}">
    <meta name="stripe-account-id" content="{{ $account->auth_id }}">
    <meta name="destination-charge" content="{{ $account->default_currency_code === \App\Enumerations\CurrencyCode::SGD && $product->currency_code === $account->default_currency_code ? 'true' : 'false' }}">
    <meta name="product-id" content="{{ $product->id }}">
    @if ($product->has_variations)
        <meta name="product-amount" content="{{ $product->variation->first(function ($variation) use ($product) {
            if (!$variation->isAvailable()) {
                return false;
            } elseif ($product->is_manageable && $variation->quantity < 1) {
                return false;
            }

            return true;
        })->amount ?? $product->amount }}">
    @else
        <meta name="product-amount" content="{{ $product->price }}">
    @endif
    <meta name="currency-code" content="{{ $product->currency_code }}">
    <meta name="zero-decimal" content="{{ \App\Enumerations\CurrencyCode::isZeroDecimal($product->currency_code) ? 'true' : 'false' }}">
    <meta property="og:title" content="{{ $product->name }}">
    <meta name="twitter:title" content="{{ $product->name }}">
    @if ($hasRemark)
        <meta property="og:description" content="{{ str_limit($product->remark) }}">
        <meta name="twitter:description" content="{{ str_limit($product->remark) }}">
    @endif
    @if (isset($productImage))
        <meta property="og:image" content="{{ $productImage }}">
        <meta name="twitter:image" content="{{ $productImage }}">
        <meta name="twitter:card" content="summary_large_image">
    @elseif (isset($logoImage))
        <meta property="og:image" content="{{ $logoImage }}">
        <meta name="twitter:image" content="{{ $logoImage }}">
        <meta name="twitter:card" content="summary_large_image">
    @endif
    <meta property="og:url" content="{{ route('checkout.express', $product->id) }}">

@endpush

@section('app-content')
    <div id="errorBar" class="alert alert-danger rounded-0 border-top-0 border-left-0 border-right-0 d-none mb-0" role="alert">
        <div class="container text-center small"></div>
    </div>
    <div id="demo" class="alert alert-warning rounded-0 border-top-0 border-left-0 border-right-0 mb-0">
        <div class="container text-center small">
            <strong>Demo in test mode.</strong> This app is running in test mode. You will not be charged.
        </div>
    </div>
    <nav class="navbar navbar-expand-md navbar-light bg-white border-bottom shadow-sm mb-4">
        <div class="container container-md">
            @if ($account->is_cart_enabled)
                <a class="nav-link" href="{{ $account->username ? route('checkout.store', $account->username) : route('checkout.store-id', $account->id) }}"><i class="fas fa-home"></i></a>
            @endif
            <div class="navbar-text mx-auto">
                @isset ($logoImage)
                    <img src="{{ $logoImage }}" height="64" class="rounded"><br>
                @else
                    <img src="{{ asset('img/hitpay.png') }}" height="32" alt="{{ $app_name }}">
                @endisset
            </div>
            @if (session()->has('cart'))
                @php($quantity = collect(session()->get('cart.products', []))->sum('quantity'))
                <a class="nav-link" href="{{ route('shop.cart') }}"><i class="fas fa-shopping-cart"></i> ({{ $quantity }})</a>
            @else
                <span class="nav-link text-light"><i class="fas fa-shopping-cart"></i></span>
            @endif
        </div>
    </nav>
    <div class="container container-md mb-3">
        <div class="bg-white rounded shadow-sm p-3 mb-3">
        <h1 class="h1 h1-rs text-uppercase text-center font-weight-bold mb-3">{{ $product->name }}</h1>
            @isset ($productImage)
                <img src="{{ $productImage }}" class="rounded img-fluid mb-3">
            @endisset
            <div class="row justify-content-between {{ ($showButton = ($hasRemark || $shippableCountries)) ? 'mb-3' : '' }}">
                @if (!$product->has_variations)
                    <div class="col-12 col-sm-auto d-flex align-items-center text-sm-right order-sm-1 mb-2 mb-sm-0">
                        <span class="text-primary font-weight-bold h2 h1-rs mb-0">
                            <small id="productCurrencyCode" data-currency-code="{{ $product->currency_code }}">
                                {{ $product->currency_code }}
                            </small>
                            <span id="productAmount" data-amount="{{ $product->amount }}">
                                {{ formatAmountWithCurrency($product->amount, $product->currency_code, false) }}
                            </span>
                        </span>
                    </div>
                @endif
                <div class="col-12 col-sm-auto d-flex align-items-center order-sm-0">
                    <span id="merchantInformation">
                        <small class="d-block text-muted">Merchant</small>
                        <span class="h6 mb-0">{{ $account->display_name ?? $account->name }}</span>
                    </span>
                </div>
            </div>
            @if ($showButton)
                <div class="collapse" id="description">
                    <div class="small mb-3">
                        @if ($hasRemark)
                            <div class="border-top pt-3">
                                <h5 class="mb-3">Description</h5>
                                @php($remark = preg_split('/\n|\r\n?/', $product->remark))
                                @foreach ($remark as $line)
                                    @if (($line = trim($line)) && strlen($line) > 0)
                                        <p class="text-dark">{{ $line }}</p>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                        @if ($shippableCountries)
                            <div class="border-top pt-3">
                                <h5 class="mb-3">Shipping {{ $shippableCountries > 0 ? 'Countries & Fees' : 'Country & Fee'}}</h5>
                                <ul class="list-unstyled mb-0">
                                    @foreach ($shippableCountry as $key => $group)
                                        @foreach ($group as $country)
                                            @include('checkout.express.shipping-item', [
                                                'amount' => $country['amount'],
                                                'method_name' => $country['method_name'],
                                                'calculation' => $country['calculation'],
                                                'country_code' => $key,
                                                'currency_code' => $account->is_cart_enabled ? $account->default_currency_code : $product->currency_code,
                                                'description' => null,
                                            ])
                                        @endforeach
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
                @if ($hasRemark)
                    @php($moreButtonText[] = 'Description')
                @endif
                @if ($shippableCountries)
                    @php($moreButtonText[] = 'Shipping')
                @endif
                <button id="descriptionBtn" class="btn btn-block btn-sm btn-dark" data-toggle="collapse" href="#description"
                        role="button" aria-expanded="false" aria-controls="description"
                        data-text="{{ $moreButtonText = implode(' & ', $moreButtonText) }}">
                    Show {{ $moreButtonText }}
                </button>
            @endif
        </div>
        <form id="payment-form" class="checkout bg-white rounded shadow-sm p-3 mb-3">
            <h2 class="h6 text-uppercase text-primary font-weight-bold mb-3">Select Options</h2>
            @if ($product->has_variations)
                <div class="form-group">
                    <select id="variationSelection" class="form-control bg-main" title="Variations" name="variation_option">
                        @foreach ($product->variation as $variation)
                            @continue(!$variation->isAvailable())
                            @php($values = array_filter([
                                $variation->variant_1_value,
                                $variation->variant_2_value,
                                $variation->variant_3_value,
                            ]))
                            <option value="{{ $variation->id }}" data-amount="{{ $variation->amount ?? $product->amount }}" {{ $product->is_manageable && $variation->quantity < 1 ? 'disabled' : '' }}>
                                {{ implode(' · ', $values) }} -
                                {{ formatAmountWithCurrency($variation->amount ?? $product->amount, $product->currency_code) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="form-group">
                <div class="input-group">
                    @if ($product->isManageable())
                        <input type="number" name="quantity" class="form-control bg-main" title="Quantity" value="1" disabled>
                    @else
                        <input type="number" name="quantity" class="form-control bg-main" title="Quantity" value="1" required>
                    @endif
                    <div class="input-group-append">
                        <span class="input-group-text">unit</span>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <textarea name="remark" class="form-control bg-main" title="Remark" rows="2"
                          placeholder="Buyer’s remarks to seller, e.g. color, size or any customisation (Optional)"></textarea>
            </div>
            @if ($canPickup)
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="isPickingUp"{{ $shippableCountries ? '' : 'checked disabled' }}>
                        <label class="custom-control-label" for="isPickingUp">I want to pickup the product</label>
                    </div>
                    <small class="form-text text-muted">Pickup Address: {{ $account->extra_data['store_address'] }}</small>
                </div>
            @else
                <div id="payment-request" class="mb-3">
                    <div id="payment-request-button">
                        <div class="text-center col-auto col-sm-10 col-md-9 mx-auto">
                            <div class="mb-2">
                                <img src="{{ asset('checkout/img/apple-pay.svg') }}" height="36">
                                <img src="{{ asset('checkout/img/g-pay.png') }}" class="border border-dark rounded" height="36">
                            </div>
                            <p class="small mb-0">To checkout with Apple Pay or Google Pay, open this link in Safari (iPhone/iPad/Mac) or Chrome (Android/ Chrome Desktop)</p>
                        </div>
                    </div>
                </div>
                <p class="text-center small">Or enter your {{ $shippableCountries ? 'shipping and ' : '' }} payment details below</p>
            @endif
            <h2 class="h6 text-uppercase text-primary font-weight-bold mb-3">Buyer Details</h2>
            <div id="formDiv" class="mb-3">
                <div class="details">
                    <input name="name" type="text" class="form-control bg-main" placeholder="Full name" required>
                    <input id="emailInput" name="email" type="email" class="form-control bg-main" placeholder="Email address" required>
                </div>
            </div>
            @if ($shippableCountries)
                <div id="formDiv2">
                <h2 class="h6 text-uppercase text-primary font-weight-bold mb-3">Shipping Information</h2>
                <div class="mb-3">
                    <div class="details">
                        @if ($shippableCountries)
                            <input id="addressInput" name="address" type="text" class="form-control bg-main" placeholder="Address" required>
                            <input id="cityInput" name="city" type="text" class="form-control bg-main" placeholder="City" required>
                            <input id="stateInput" name="state" type="text" class="form-control bg-main d-none" placeholder="State">
                            <input id="postalCodeInput" name="postal_code" class="form-control bg-main" placeholder="Postal code" required>
                            <select id="countrySelection" name="country" class="country form-control custom-select bg-main"
                                    title="Country" data-default-country-code="{{ $account->country_code }}"
                                    required{{ !$globalShippable && $shippableCountries === 1 ? ' disabled' : ''}}>
                                @if ($globalShippable)
                                    @foreach (\App\Enumerations\CountryCode::listConstants() as $country)
                                        <option value="{{ $country }}" data-country-name="@lang('countries.'.$country)">
                                            @lang('countries.'.$country)
                                        </option>
                                    @endforeach
                                @else
                                    @foreach ($shippableCountry as $key => $group)
                                        @foreach ($group as $country)
                                            <option value="{{ $key }}"
                                                    data-country-name="@lang('countries.'.$key)">
                                                @lang('countries.'.$key)
                                            </option>
                                            @break
                                        @endforeach
                                    @endforeach
                                @endif
                            </select>
                        @endif
                    </div>
                </div>
                <div class="mb-3">
                    <div class="details">
                        <select id="shippingOptionSelection" name="shipping_option"
                                class="country form-control custom-select bg-main"
                                title="Shipping Method" required>
                        </select>
                    </div>
                    <span id="shippingNote" class="form-text text-muted small"></span>
                </div>
                </div>
            @endif
            <div class="row justify-content-sm-between align-items-center mb-3">
                <div class="col-7 col-sm-auto">
                    <h2 class="h6 text-uppercase text-primary font-weight-bold mb-0">
                        Payment Information
                    </h2>
                </div>
                <div class="col-5 col-sm-auto">
                    <img src="{{ asset('checkout/img/visa.png') }}" height="14">
                    <img src="{{ asset('checkout/img/master.png') }}" height="14">
                    <img src="{{ asset('checkout/img/amex.png') }}" height="14">
                </div>
            </div>
            <div class="mb-2">
                <div id="card-element" class="form-control bg-main p-2"></div>
                <small id="card-errors" class="form-text text-danger"></small>
            </div>
            <button type="submit" id="submitBtn" class="btn btn-block btn-primary py-2">
                <span class="text-white-50">Pay </span>
                <strong id="submitBtnAmount">{{ formatAmountWithCurrency($product->amount, $product->currency_code) }}</strong>
            </button>
            <div id="failedMessage" class="alert alert-danger small py-2 mt-2 mb-0 d-none"></div>
            <div class="text-center text-muted small mt-2">Your card details are never stored on our servers and is fully encrypted for payment processing</div>
        </form>
        @if (session()->has('cart') || $account->is_cart_enabled)
            <p class="text-center text-secondary">OR</p>
            <div class="form-group">
                @php($canAdd = !session()->has('cart') || session()->get('cart.merchant_id') === $product->account_id)
                <button id="addToCartBtn" class="btn btn-block btn-success py-2 font-weight-bold"{{ $canAdd ? null : ' disabled' }}>Add to cart</button>
                @if ($account->is_cart_enabled && !$canAdd)
                    <small class="text-muted">You can't add this product to cart because this product is from different merchant.</small>
                @elseif (session()->has('cart') && !$account->is_cart_enabled)
                    <small class="text-muted">You can't add this product to cart because this merchant has not enable cart function.</small>
                @endif
            </div>
        @endif
    </div>
    <div class="text-center mb-5">
        Report listing: <a href="mailto:{{ config('mail.from.address') }}?subject=[Report listing] {{ $product->name }}">{{ config('mail.from.address') }}</a>
    </div>
    <div class="bg-white border-top">
        <div class="container container-md small text-center py-4">
            <p><img src="{{ asset('img/hitpay.png') }}" width="120"></p>
            Powered by <a href="https://hit-pay.com" target="_blank">{{ config('app.name') }}</a>
        </div>
    </div>
@endsection

@push('body')
    <div id="processing" class="modal fade" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content border-0 shadow">
                <div class="modal-body text-center">
                    <div class="">
                        <div id="processingMessage">
                            <h3 class="h3-rs text-primary mb-3">Hang on Tight</h3>
                            <p>Your payment is being processed…</p>
                            <p class="text-danger text-uppercase font-weight-bold">Please do not refresh</p>
                            <i class="fas fa-2x fa-spinner fa-spin text-success"></i>
                        </div>
                        <div id="completedMessage" class="d-none">
                            <p>Thank you purchasing from</p>
                            <h3 class="h3-rs text-primary mb-3">{{ $account->name }}</h3>
                            <p>Your order has been received<br>Please check your email for order confirmation</p>
                            <p><img src="{{ asset('checkout/img/done.png') }}" height="32"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-center">
                    <img src="{{ asset('img/hitpay.png') }}" height="20" alt="{{ $app_name }}">
                </div>
            </div>
        </div>
    </div>
    @if ($shippableCountries)
        <script>
            const shippingOption = {!! $shippingOptions !!};
        </script>
    @endif
    <script src="https://js.stripe.com/v3/"></script>
    <script src="{{ mix('checkout/js/express-checkout.js') }}"></script>
@endpush
