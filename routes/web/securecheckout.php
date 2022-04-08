<?php

Route::post('shopify/payment', 'CheckoutController@shopify')
    ->name('securecheckout.shopify.payment')
    ->middleware('shopify.checkout')
;

Route::post('shopify/charge', 'ShopifyCheckoutController@store')
    ->name('securecheckout.shopify.payment.charge')
    ->middleware('shopify.access');

Route::post('shopify/refund', 'ShopifyRefundController@store')
    ->name('securecheckout.shopify.payment.refund')
    ->middleware('shopify.access');

Route::post('shopify/webhook','ShopifyWebhookController@store')
    ->name('securecheckout.shopify.webhook');

// OCBC completed route for shopify
Route::get('shopify/payment', 'CheckoutController@shopifyCompleted')
    ->name('securecheckout.shopify.payment.completed')
;

// OCBC completed route for woocommerce
Route::get('payment-gateway/woocommerce/checkout', 'CheckoutController@shopifyCompleted')
    ->name('securecheckout.shopify.payment.completed')
;

Route::post('shopify/callback/{business_id}', 'CheckoutController@shopifyCallback')
    ->name('securecheckout.callback')
;

Route::prefix('payment-request/{business_slug}')->name('securecheckout.payment.request.')->group(function () {
    Route::get('', 'PaymentRequestCheckoutController@businessPaymentCheckout')
        ->name('business.checkout')
        ->middleware('payment.request.access')
    ;
});

Route::prefix('payment-request/{business_slug}/{payment_request_id}')->name('securecheckout.payment.request.')->group(function () {
    Route::get('/checkout', 'PaymentRequestCheckoutController@paymentCheckout')
        ->name('checkout')
        ->middleware('payment.request.access')
    ;
    Route::get('/expired', 'PaymentRequestCheckoutController@paymentCheckoutExpired')
        ->name('expired')
    ;
});

Route::prefix('payment-request/{p_charge}')->name('securecheckout.payment.request.')->group(function () {
    Route::get('/completed', 'PaymentRequestCheckoutController@paymentCheckoutCompleted')
        ->name('completed')
    ;
});

Route::prefix('payment-gateway/{provider}')->name('securecheckout.payment.gateway.')->group(function () {
    Route::post('/checkout', 'PaymentGatewayCheckoutController@paymentCheckout')
        ->name('checkout')
        ->middleware('payment.gateway.checkout')
    ;
});

Route::get('{business_id}/recurring-plan/{recurring_plan_id}', 'RecurringPlanController@show')
    ->name('recurring-plan.show');
Route::post('{business_id}/recurring-plan/{recurring_plan_id}', 'RecurringPlanController@update')
    ->name('recurring-plan.update');
Route::post('{business_id}/recurring-plan/{recurring_plan_id}/setup-intent', 'RecurringPlanController@getSetupIntent')
    ->name('recurring-plan.setup');

Route::get('demo/paynow/{hash}', 'PayNowMockController@show')->name('paynow.mock.page');
Route::post('demo/paynow/{hash}', 'PayNowMockController@process')->name('paynow.mock.process');

Route::group(['prefix' => 'xero', 'as' => 'xero.checkout.'], function() {
    Route::get('/', 'XeroCheckoutController@show')->name('invoice');
    Route::post('confirm', 'XeroCheckoutController@confirm')->name('confirm');
});

Route::namespace('Shop')->group(function () {
  Route::prefix('redirect')->name('redirect.')->group(function () {
    Route::get('zip', 'ZipController@handleRedirect')->name('zip');
  });
});
