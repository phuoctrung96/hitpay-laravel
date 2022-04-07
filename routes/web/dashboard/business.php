<?php

$basePath = base_path('routes/web/dashboard/business/settings');

Route::prefix('settings')->name('settings.')->group(function () use ($basePath) {
    Route::prefix('bank-accounts')->name('bank-accounts.')->group("{$basePath}/bank-accounts.php");
    Route::prefix('payment-providers')->name('payment-providers.')->group("{$basePath}/payment-providers.php");
});
