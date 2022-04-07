<?php

use App\Http\Controllers\Dashboard\Business\Settings\PaymentProviders\Stripe\CustomAccountController;

// Route::get('/');

// Stripe - Custom Connected Account. We named it platform for our users.
//
Route::prefix('platform')->name('platform.')->group(function () : void {
    $customAccountControllerClass = '\\'.CustomAccountController::class;

    Route::get('/', "{$customAccountControllerClass}@showHomepage")->name('homepage');

    Route::prefix('custom-account')->name('custom-account.')->group(function () use (
        $customAccountControllerClass
    ) : void {
        Route::get('redirect', "{$customAccountControllerClass}@redirectToStripeAccountPage")->name('redirect');
        Route::get('callback', "{$customAccountControllerClass}@callbackForStripeAccount")->name('callback');
    });
});

// PayNow (Powered by DBS)
//
Route::prefix('paynow')->name('paynow.')->group(function () : void { });

// Stripe - Standard Connected Account. Available for existing businesses and businesses locating in countries
// which HitPay doesn't have license.
//
Route::prefix('stripe')->name('stripe.')->group(function () : void { });
