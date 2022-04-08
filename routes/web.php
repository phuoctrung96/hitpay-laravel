<?php

use Illuminate\Support\Facades\Config;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::domain(Config::get('app.domain'))->group(base_path('routes/web/root.php'));
Route::domain(Config::get('app.subdomains.admin'))->group(base_path('routes/web/admin.php'));
Route::domain(Config::get('app.subdomains.dashboard'))->group(base_path('routes/web/dashboard.php'));
Route::domain(Config::get('app.subdomains.checkout'))->group(base_path('routes/web/checkout.php'));
Route::domain(Config::get('app.subdomains.securecheckout'))->group(base_path('routes/web/securecheckout.php'));
Route::domain(Config::get('app.subdomains.shop'))->group(base_path('routes/web/shop.php'));
Route::domain(Config::get('app.subdomains.invoice'))->group(base_path('routes/web/invoice.php'));
Route::domain(Config::get('app.subdomains.merchant'))->group(function () {
    Route::any('{v?}/{v1?}/{v2?}/{v3?}/{v4?}/{v5?}/{v6?}', 'OldMerchantRedirectionController@redirect');
});

Route::domain(Config::get('app.shop_domain'))->group(base_path('routes/web/shop.php'));

Route::get('storage/{path1}/{path2}/{path3?}/{path4?}', 'LocalStorageController@getFile')->name('local-storage');

// Ecwid
Route::get('ecwid/settings', 'EcwidController@settings');
Route::get('ecwid/redirect', 'EcwidController@redirect');
Route::post('ecwid/save_settings', 'EcwidController@save_settings');
Route::post('ecwid/load_form', 'EcwidController@load_form');
Route::post('ecwid/payment', 'EcwidController@payment');
Route::post('ecwid/hitpay', 'EcwidController@hitpay');
//Route::domain(Config::get('/ecwid/payment',[EcwidController::class, 'payment']));
Route::get('close', 'CloseController')->name('close');
Route::get('callback', 'Dashboard\Business\VerificationController@callbackSandbox')->name('callback');
