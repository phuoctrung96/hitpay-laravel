<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::namespace('Api')->name('api.')->group(function () {
    Route::prefix('v1')->name('v1.')->group(function () {
        Route::get('/', 'HomeController@index')->name('home');

        Route::get('/code-version', 'CodeVersionController@index')->name('code-version.index');

        Route::resource('business', 'BusinessController')
            ->parameter('business', 'business_id')
            ->except('create', 'edit', 'destroy')
            ->names('business');

        Route::put('business/{business_id}/identifier', 'BusinessController@updateIdentifier')
            ->name('business.update-identifier');
        Route::get('business/{business_id}/report/daily', 'BusinessController@getDailyReport')
            ->name('business.report.daily');

        Route::namespace('Business')->name('business.')->group(function () {
            Route::resource('business.charge', 'ChargeController')
                ->parameter('business', 'business_id')
                ->parameter('charge', 'b_charge')
                ->except('create', 'edit')
                ->names('charge');

            Route::post('business/{business_id}/charge/send', 'ChargeController@export')->name('charge.export');
            Route::post('business/{business_id}/charge/{b_charge}/send', 'ChargeController@send')->name('charge.send');

            Route::namespace('PayNow')->prefix('business/{business_id}')->group(function () {
                Route::post('charge/paynow/payment-intent', 'ChargeController@createPaymentIntent')->name('charge.paynow.payment-intent');
                Route::get('payment-provider/paynow/payout', 'PayoutController')->name('payment-provider.paynow.payout');
            });

            Route::namespace('Stripe')
                ->prefix('business/{business_id}/charge/stripe')
                ->name('charge.stripe.')
                ->group(function () {
                    Route::post('connection_token', 'ChargeController@getConnectionToken')->name('connection_token');
                    Route::post('payment-intent', 'ChargeController@createPaymentIntent')->name('payment-intent');
                    Route::post('payment-intent/{id}/confirm', 'ChargeController@confirmPaymentIntent')->name('payment-intent.confirm');
                    Route::post('payment-intent/{id}', 'ChargeController@capturePaymentIntent')->name('payment-intent.capture');
                    Route::post('wechat-source', 'ChargeController@createWechatSource')->name('wechat-pay-source');
                });

            Route::resource('business.customer', 'CustomerController')
                ->parameter('business', 'business_id')
                ->parameter('customer', 'b_customer')
                ->except('create', 'edit')
                ->names('customer');

            Route::post('business/{business_id}/customer/{b_customer}/charge', 'CustomerController@chargesIndex')
                ->name('customer.charge');

            Route::post('business/{business_id}/customer/create-by-email', 'CustomerController@createByEmail')
                ->name('customer.create.by.email');

            Route::resource('business.discount', 'DiscountController')
                ->parameter('business', 'business_id')
                ->parameter('discount', 'b_discount')
                ->except('create', 'edit')
                ->names('discount');

            Route::resource('business.order', 'OrderController')
                ->parameter('business', 'business_id')
                ->parameter('order', 'b_order')
                ->except('create', 'edit')
                ->names('order');

            Route::post('business/{business_id}/order/{b_order}/status/{status}',
                'OrderController@updateMessageOrStatus')->name('order.charge.status');
            Route::post('business/{business_id}/order/{b_order}/charge',
                'OrderChargeController@createCharge')->name('order.charge.create');
            Route::post('business/{business_id}/order/{b_order}/charge/stripe/payment-intent/{method?}',
                'OrderChargeController@createPaymentIntent')->name('order.charge.stripe.payment-intent');
            Route::post('business/{business_id}/order/{b_order}/charge/stripe/wechat-source',
                'OrderChargeController@createWechatSource')->name('order.charge.stripe.wechat-source');
            Route::post('business/{business_id}/order/{b_order}/charge/paynow/payment-intent',
                'OrderChargeController@createPayNowPaymentIntent')->name('order.charge.paynow.payment-intent');
            Route::post('business/{business_id}/order/{b_order}/send', 'OrderController@send')->name('order.send');

            Route::resource('business.order.product', 'OrderedProductController')
                ->parameter('business', 'business_id')
                ->parameter('order', 'b_order')
                ->parameter('product', 'b_ordered_product')
                ->only('store', 'update', 'destroy')
                ->names('order.product');

            Route::resource('business.payment-card', 'PaymentCardController')
                ->parameter('business', 'business_id')
                ->parameter('payment-card', 'b_payment_card')
                ->except('create', 'edit')
                ->names('payment-card');

            Route::namespace('Stripe')
                ->prefix('business/{business_id}/payment-provider/stripe')
                ->name('payment-provider.stripe.')
                ->group(function () {
                    Route::get('/', 'PaymentProviderController@showAccountOrUrl')->name('show-account-or-url');
                    Route::post('/', 'PaymentProviderController@authorizeAccount')->name('authorize-account');
                    Route::delete('/', 'PaymentProviderController@deauthorizeAccount')->name('deauthorize-account');
                    Route::get('payout', 'PayoutController')->name('payout');
                });

            Route::resource('business.product', 'ProductController')
                ->parameter('business', 'business_id')
                ->parameter('product', 'b_product')
                ->except('create', 'edit')
                ->names('product');

            Route::resource('business.product.image', 'ProductImageController')
                ->parameter('business', 'business_id')
                ->parameter('product', 'b_product')
                ->except('create', 'edit')
                ->names('product.image');

            Route::post('business/{business_id}/product/{b_product}/send', 'ProductController@send')
                ->name('product.send');

            Route::resource('business.product.image', 'ProductImageController')
                ->parameter('business', 'business_id')
                ->parameter('product', 'b_product')
                ->parameter('image', 'b_product_image')
                ->only('store', 'destroy')
                ->names('product.image');

            Route::resource('business.product-category', 'ProductCategoryController')
                ->parameter('business', 'business_id')
                ->parameter('product-category', 'b_product_category')
                ->except('create', 'edit')
                ->names('product-category');

            Route::resource('business.shipping', 'ShippingController')
                ->parameter('business', 'business_id')
                ->parameter('shipping', 'b_shipping')
                ->except('create', 'edit')
                ->names('shipping');

            Route::resource('business.tax', 'TaxController')
                ->parameter('business', 'business_id')
                ->parameter('tax', 'b_tax')
                ->except('create', 'edit')
                ->names('tax');

            Route::resource('business.tax-settings', 'TaxSettingController')
                ->parameter('business', 'business_id')
                ->parameter('tax-settings', 'b_tax_setting')
                ->except('create', 'edit')
                ->names('tax-settings');

            Route::resource('business.invoice', 'InvoiceController')
                ->parameter('business', 'business_id')
                ->parameter('invoice', 'b_invoice')
                ->names('invoice');

            Route::post('business/{business_id}/invoice/{b_invoice}/resend', 'InvoiceController@resend')
                ->name('invoice.resend');

            Route::get('payment-intent/{stripPaymentIntentId}', 'Plugin\ChargeController@getPaymentIntentOnly')->name('business.plugin.payment-intent-only');

            Route::namespace('Plugin')->prefix('business/{business_id}/plugin')->name('business.plugin.')->group(function () {
                Route::get('/charge/payment-intent/{stripPaymentIntentId}', 'ChargeController@getPaymentIntent')->name('charge.create');
                Route::post('/charge/payment-intent/{stripPaymentIntentId}/capture', 'ChargeController@capturePaymentIntent')->name('charge.capture');
                Route::put('/charge/payment-intent/{stripPaymentIntentId}/confirm', 'ChargeController@confirmPaymentIntent')->name('charge.confirm');
                Route::post('/charge/{b_charge}/create-payment-intent', 'ChargeController@createPaymentIntent')->name('charge.create.payment.intent');
                Route::post('/charge/{b_charge}/cash', 'ChargeController@createCash')->name('charge.cash');
                Route::post('/charge/{b_charge}/cancel', 'ChargeController@cancelCharge')->name('charge.cancel');
                Route::get('/charge/{b_charge}/charge-completed', 'ChargeController@chargeCompleted');
                Route::post('/charge/{b_charge}/update-payment-intent', 'ChargeController@updatePaymentIntent')->name('charge.update.payment.intent');
            });

            Route::apiResource('payment-requests', 'PaymentRequestController')
                ->middleware('payment.request.auth')
            ;

            Route::post('refund', 'RefundController@store')
                ->name('refund.store')->middleware('payment.request.auth');

            Route::apiResource('subscription-plan', 'SubscriptionPlanController')
                ->middleware('payment.request.auth');

            Route::apiResource('recurring-billing', 'RecurringBillingController')
                ->middleware('payment.request.auth');

            Route::post('business/{business_id}/logo', 'BusinessLogoController@store')
                ->name('business.store.logo');

            Route::delete('business/{business_id}/logo', 'BusinessLogoController@destroy')
                ->name('business.delete.logo');

            Route::get('business/{business_id}/tax-detail', 'BusinessTaxDetailController@show')
                ->name('business.tax_detail.show');

            Route::put('business/{business_id}/tax-detail', 'BusinessTaxDetailController@update')
                ->name('business.tax_detail.update');
        });

        // !!! Seems to unused
        Route::prefix('charge')->name('charge.')->group(function () {
            Route::get('/create', 'ChargeController@create')->name('create');
            Route::get('/void', 'ChargeController@void')->name('void');
            Route::get('/refund', 'ChargeController@refund')->name('refund');
        });
        //---

        Route::prefix('order')->name('order.')->group(function () {
          Route::get('/status/{paymentIntent}', 'OrderStatusController@status')->name('status');
        });

        Route::get('callback/stripe', 'WhateverController')->name('dumper.stripe');
        Route::get('currency', 'MiscellaneousController@currency');
        Route::get('dump', 'WhateverController')->name('dumper');
        Route::post('firebase/token', 'FirebaseTokenController')->name('firebase.token');
        Route::post('oauth/token', '\Laravel\Passport\Http\Controllers\AccessTokenController@issueToken')
            ->name('passport.token')
            ->middleware('throttle');

        Route::prefix('stripe/oauth')->group(function () {
            Route::post('/search', 'MiscellaneousController@getStripeOAuthUrl')->name('stripe.oauth.search');
            Route::post('/process', 'MiscellaneousController@doStripeOauthLogin')->name('stripe.oauth.process');
        });

        Route::prefix('user')->group(function () {
            Route::post('/', 'UserController@register')->name('user.register');
            Route::get('/', 'UserController@showProfile')->name('user.show-profile');
            Route::put('/', 'UserController@updateBasicInformation')->name('user.update-basic-information');
            Route::put('credentials', 'UserController@setup')->name('user.setup');
            Route::put('email', 'UserController@updateEmail')->name('user.update-email');
            Route::post('password/email', 'ForgotPasswordController@sendResetLinkEmail')->name('password.request');
            Route::put('password', 'UserController@updatePassword')->name('user.update-password');
            Route::put('phone-number', 'UserController@updatePhoneNumber')->name('user.update-phone-number');
        });

        Route::namespace('Plugin')->prefix('plugin/business/{business_id}')->name('plugin.')->group(function () {
            Route::get('/token', 'BusinessController@getConnectionToken')
                ->name('business.token');
        });
    });

    Route::prefix('webhook')->name('webhook.')->group(function () {
        Route::post('paynow', 'PayNowWebhookController')->name('paynow');

        Route::post('shopee', 'ShopeeWebhookController')->name('shopee');

        Route::prefix('shopify')->name('shopify.')->group(function () {
            Route::prefix('business/{business_id}')->group(function () {
                Route::post('inventory_items/created', 'ShopifyController@inventoryItemsCreated')
                    ->name('inventory-items.created');
                Route::post('inventory_items/deleted', 'ShopifyController@inventoryItemsDeleted')
                    ->name('inventory-items.deleted');
                Route::post('inventory_items/updated', 'ShopifyController@inventoryItemsUpdated')
                    ->name('inventory-items.updated');
                Route::post('inventory_levels/updated', 'ShopifyController@inventoryLevelsUpdated')
                    ->name('inventory-levels.updated');
                Route::post('locations/delete', 'ShopifyController@locationsDeleted')->name('locations.deleted');
                Route::post('products/created', 'ShopifyController@productsCreated')->name('products.created');
                Route::post('products/deleted', 'ShopifyController@productsDeleted')->name('products.deleted');
                Route::post('products/updated', 'ShopifyController@productsUpdated')->name('products.updated');
                Route::post('shope/updated', 'ShopifyController@shopUpdated')->name('shop.updated');
                Route::post('subscription/updated', 'ShopifyController@appSubscriptionUpdated')
                    ->name('app-subscription.updated');
                Route::post('uninstall', 'ShopifyController@uninstalled')->name('uninstalled');
            });

            Route::post('redact/customer', 'ShopifyController@respondOkay')->name('redact.customer');
            Route::post('redact/shop', 'ShopifyController@redactShop')->name('redact.shop');
            Route::post('request/customer', 'ShopifyController@respondOkay')->name('request.customer');

            Route::post('customer-data-request', 'ShopifyCustomerDataRequestWebhook')->name('customer.data.request');
            Route::post('customer-redact', 'ShopifyCustomerRedactWebhook')->name('customer.redact');
            Route::post('shop-redact', 'ShopifyShopRedactWebhook')->name('shop.redact');
        });

        Route::post('stripe/{platform}', 'StripeWebhookController')->name('stripe');
        Route::post('stripe/{platform}/connect', 'StripeConnectWebhookController')->name('stripe.connect');
        Route::post('cognitohq', 'CognitohqWebhookController')->name('cognitohq');
    });
});

Route::prefix('v1')->name('v1.')->group(function () {
  Route::get('business/{payment_request_id}/checkout-dropin-pr', 'PaymentRequestCheckoutController@businessCheckoutDropin')
    ->name('business.checkout-dropin');

  Route::get('business/{business_slug}/checkout-dropin-default', 'PaymentRequestCheckoutController@businessCheckoutDropinDefault')
    ->name('business.checkout-dropin');
});

// !!! Should be moved to securecheckout
Route::namespace('Shop')->name('api.')->group(function () {
  Route::prefix('redirect')->name('redirect.')->group(function () {
    Route::get('grabpay', 'GrabPayController@handleRedirect')->name('grabpay');
    Route::get('alipay', 'AliPayController@handleRedirect')->name('alipay');
  });
});
