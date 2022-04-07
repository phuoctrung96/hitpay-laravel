<?php
Route::name('migrated-api.')->prefix('api')->namespace('MigratedApi')->group(function () {
    Route::put('firebase/token', 'FirebaseTokenController@update');
    Route::post('firebase/token/update', 'FirebaseTokenController@update');
    Route::get('payout', 'PayoutController@index');
    Route::get('user', 'UserController@show');
    Route::post('user/store-url', 'UserController@setStoreUrl');

    Route::get('product', 'ProductController@index');
    Route::get('product/search', 'ProductController@search');
    Route::post('product', 'ProductController@store');
    Route::put('product/{id}', 'ProductController@update');
    Route::get('product/{id}', 'ProductController@show');
    Route::put('product/{id}/archive', 'ProductController@archive');
    Route::post('product/{id}/update', 'ProductController@update');

    Route::post('order', 'OrderController@create');
    Route::get('order', 'OrderController@index');
    Route::get('order/{id}', 'OrderController@show')->name('order.show');
    Route::put('order/{id}/pay', 'OrderController@pay');
    Route::put('order/{id}/{status}', 'OrderController@update');

    Route::post('stripe/authenticate', 'StripeController@authenticate');
    Route::get('stripe/callback', 'WhateverController')->name('stripe.callback');
    Route::get('stripe/redirect', 'StripeController@redirect');

    Route::get('order/{id}/{method}/callback', 'WhateverController')->name('order.callback');

    Route::get('currency/{method?}', 'TransactionController@currency');

    Route::get('transaction', 'TransactionController@index');
    Route::post('transaction/export', 'ReportController@sendTransactionList');
    Route::post('transaction/intent', 'PaymentCardController@createPaymentIntent')->name('payment-intent.create');
    Route::post('transaction/log', 'TransactionController@log');
    Route::get('transaction/today', 'TransactionController@getTodayCollection');
    Route::get('transaction/{id}', 'TransactionController@show');
    Route::post('transaction/{id}/send', 'TransactionController@send');
    Route::post('transaction/{id}/process', 'TransactionController@showRepeat')->name('payment-intent.process');
    Route::put('transaction/{id}/refund', 'TransactionController@refund');
    Route::get('transaction/{id}/{method}/callback', 'WhateverController')->name('charge.callback');
    Route::post('transaction/{id}/{method}/charge', 'TransactionController@showRepeat');
    Route::post('transaction/{type}/source', 'ChinaPaymentMethodController@createSource');
});
