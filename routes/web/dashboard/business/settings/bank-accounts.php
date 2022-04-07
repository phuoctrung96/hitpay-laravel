<?php

use App\Http\Controllers\Dashboard\Business\Settings\BankAccountController;

$bankAccountControllerClass = '\\'.BankAccountController::class;

Route::get('/', "{$bankAccountControllerClass}@showHomepage")->name('homepage');
Route::post('/', "{$bankAccountControllerClass}@store")->name('store');
Route::get('create', "{$bankAccountControllerClass}@showCreatePage")->name('create-page');

Route::prefix('{b_bank_account}')->group(function () use ($bankAccountControllerClass) {
    Route::put('/', "{$bankAccountControllerClass}@update")->name('update');
    Route::delete('/', "{$bankAccountControllerClass}@destroy")->name('destroy');
    Route::get('edit', "{$bankAccountControllerClass}@showEditPage")->name('edit-page');
    Route::put('for-{payment_provider}', "{$bankAccountControllerClass}@setDefaultFor")->name('default');
});
