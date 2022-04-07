<?php

Route::get('product/{id}/checkout', 'CheckoutRedirectionController@productId')->name('checkout.express');
Route::get('product/{id}', 'CheckoutRedirectionController@productId')->name('checkout.product');
Route::get('store/{username}', 'CheckoutRedirectionController@storeUsername')->name('checkout.store');
Route::get('store/id/{id}', 'CheckoutRedirectionController@storeUsername')->name('checkout.store-id');
Route::get('s/{id}', 'CheckoutRedirectionController@s')->name('short-url')->where('id', '[a-zA-Z0-9]{6}');
