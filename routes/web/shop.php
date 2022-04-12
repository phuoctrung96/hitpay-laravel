<?php

Route::namespace('Shop')->group(function () {
    Route::get('{fb_slot}/products', 'ProductController@generateFBFeedProducts');
    Route::get('/', 'HomeController@showHomepage')->name('shop');
    Route::get('s/{shortcut_id}', 'ShortcutController@redirect')->name('shortcut');
    Route::prefix('business')->name('business.')->group(function () {
        Route::prefix('{business_id}')->group(function () {
            Route::get('/', 'HomeController@showBusinessHomepage')->name('home');
        });
    });
    Route::prefix('{business}')->name('shop')->middleware('shop.enabled')->group(function () {
        Route::get('/', 'HomeController@showBusinessHomepage')->name('.business');
        Route::get('about-us', 'HomeController@showIntroduction')->name('.introduction');

        Route::prefix('ajax/cart')->name('.cart')->group(function () {
            Route::post('/', 'CartController@addProduct')->name('.add-product');
            Route::put('{index}', 'CartController@updateProduct')->name('.update-product');
            Route::delete('{index?}', 'CartController@removeProduct')->name('.delete-product');
        });

        Route::get('cart', 'CartController@showCartPage')->name('.cart');

        Route::prefix('charge/{charge_id}')->name('.charge')->group(function () {
            Route::get('/', 'CheckoutController@getCharge');
            Route::get('alipay/callback', 'CheckoutController@showAlipayCallback')->name('.alipay');
            Route::post('payment-intent', 'CheckoutController@createPaymentIntentForCharge')->name('..payment-intent');
        });

        Route::post('checkout', 'CheckoutController@doCheckout')->name('.checkout');
        Route::get('checkout', 'CheckoutController@showPreCheckoutPage')->name('.show.checkout');
        Route::get('getJsonCoupon/{coupon_code}', 'CheckoutController@getJsonCoupon')->name('getJsonCoupon');
        Route::get('product/search', 'HomeController@searchProducts')->name('.product.search');
        Route::get('product/{product_id}', 'ProductController@showProductPage')->name('.product');
        Route::post('product/category/{category_id}', 'HomeController@getProductWithCategory')->name('.getProductCategory');
        Route::post('product/{product_id}', 'CheckoutController@doExpressCheckout')->name('.product.checkouts');
        Route::get('order/status/{order_id}', 'OrderController@status')->name('.order.status');
        Route::post('order/confirm/{order_id}', 'OrderController@confirm')->name('.order.confirm');
        Route::get('order/{id}', 'OrderController@get')->name('.order');
        Route::post('order/{id}', 'OrderController@pay')->name('.order.pay');
    });
});
