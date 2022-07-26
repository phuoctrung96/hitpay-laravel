<?php

Route::namespace('Admin')->group(function () {
    Route::get('/', 'HomeController@showHomepage')->name('admin');

    Route::name('admin.')->group(function () {
        Route::get('business', 'BusinessController@index')->name('business.index');
        Route::post('business', 'BusinessController@store')->name('business.store');
        Route::post('business/export', 'BusinessController@export')->name('business.export');
        Route::get('business/new', 'BusinessController@create')->name('business.create');
        Route::get('business/{business_id}', 'BusinessController@show')->name('business.show');
        Route::put('business/{business_id}', 'BusinessController@update')->name('business.update');
        Route::delete('business/{business_id}/{with?}', 'BusinessController@delete')->name('business.delete');
        Route::get('business/{business_id}/edit', 'BusinessController@edit')->name('business.edit');
        Route::get('business/{business_id}/wallet/{currency}', 'BusinessWalletController@showWallet')->name('business.wallet');
        Route::post('business/{business_id}/wallet/{currency}/add', 'BusinessWalletController@add')->name('business.wallet.add');
        Route::post('business/{business_id}/wallet/{currency}/deduct', 'BusinessWalletController@deduct')->name('business.wallet.deduct');
        Route::post('business/{business_id}/wallet/{currency}/set-deposit', 'BusinessWalletController@setDeposit')->name
        ('business.wallet.set-deposit');

        Route::post('business/{business_id}/compliance', 'BusinessController@updateCompliance')->name('business.compliance');

        // slack channel

        Route::get('business/{business_id}/charge', 'BusinessChargeController@index')->name('business.charge.index');
        Route::post('business/{business_id}/charge/export', 'BusinessChargeController@export')->name('business.charge.export');

        Route::put('business/{business_id}/reject', 'BusinessController@reject')->name('business.reject');
        Route::put('business/{business_id}/verify', 'BusinessController@verify')->name('business.verify');

        Route::delete('business/{business_id}/payment-provider/stripe_sg', 'BusinessPaymentProviderController@deauthorizeStripeSingaporeAccount')->name('business.payment-provider.stripe_sg.deauthorize');

        Route::prefix('business/{business_id}/payment-provider/{provider}/rate')->group(function () {
            Route::get('/', 'BusinessRateController@create')->name('business.rate.create');
            Route::post('/', 'BusinessRateController@store')->name('business.rate.store');
        });

        Route::delete('business/{business_id}/custom-rate/{rate_id}', 'BusinessRateController@destroy')->name('business.rate.destroy');

        Route::get('business/{business_id}/terminal', 'BusinessTerminalController@index')->name('business.terminal.index');
        Route::post('business/{business_id}/terminal', 'BusinessTerminalController@store')->name('business.terminal.store');
        Route::get('business/{business_id}/terminal/new', 'BusinessTerminalController@create')->name('business.terminal.create');
        Route::get('business/{business_id}/terminal/{terminal_id}', 'BusinessTerminalController@show')->name('business.terminal.show');
        Route::delete('business/{business_id}/terminal/{terminal_id}', 'BusinessTerminalController@destroy')->name('business.terminal.destroy');

        Route::get('business/{business_id}/transfer/fast-payment', 'FastPaymentTransferController@indexByBusiness')->name('business.transfer.fast-payment.index');

        Route::prefix('business/{business_id}/platform')->name('business.platform.')->group(function () {
            Route::get('/', 'BusinessPlatformController@index')->name('index');
            Route::put('/', 'BusinessPlatformController@enable')->name('enable');
            Route::delete('/', 'BusinessPlatformController@disable')->name('disable');
            Route::put('rekey', 'BusinessPlatformController@rekey')->name('rekey');
        });

        Route::post('business/{business_id}/set-payment-enabled', 'BusinessSetPaymentEnableController@update')->name('business.set_payment_enabled');

        Route::get('charge', 'ChargeController@index')->name('charge.index');
        Route::post('charge/export', 'ChargeController@export')->name('charge.export');
        Route::get('charge/uncaptured', 'ChargeController@showUncapturedPage')->name('charge.uncaptured');
        Route::post('charge/{charge}/capture', 'ChargeController@capture')->name('charge.capture');
        Route::post('charge/{charge}/notify', 'ChargeController@notifyNonIdentifiableChargeSource')->name('charge.notify.source');
        Route::get('charge/{charge}', 'ChargeController@show')->name('charge.show');
        Route::put('charge/{charge}/refund', 'ChargeController@markAsRefund')->name('charge.refund');
        Route::get('commission', 'CommissionController@index')->name('commission.index');
        Route::post('commission/export', 'CommissionController@export')->name('commission.export');
        Route::get('commission/{commission}', 'CommissionController@get')->name('commission.get');
        Route::put('commission/{commission}', 'CommissionController@update')->name('commission.update');
        Route::get('email-attachments/{any}', 'EmailAttachmentController@download')->name('email-attachment.download')->where('any', '.*');
        Route::get('failed-refund', 'ChargeController@failedRefund')->name('failed-refund.index');
        Route::get('files/email-attachments/{any?}', 'EmailAttachmentController@index')->name('email-attachment.index');

        Route::group([
            'as' => 'reconciliations.bank-statements.',
            'namespace' => 'Reconciliations',
            'prefix' => 'reconciliations/bank-statements',
        ], function () {
            Route::get('download', 'BankStatementController@download')->name('download');
            Route::post('{year}/{month}/{day}', 'BankStatementController@update')->name('update');
            Route::get('{year?}/{month?}/{day?}', 'BankStatementController@index')->name('index');
        });

        Route::post('refund/export', 'ChargeController@exportRefund')->name('refund.export');
        Route::post('referral-fees/export', 'BusinessReferralFeeExportController')->name('referral-fees.export');

        Route::get('terminal', 'TerminalController@index')->name('terminal.index');

        Route::group(['prefix' => 'partners', 'as' => 'partner.'], function () {
            Route::get('/', 'PartnerController@index')->name('index');
            Route::post('/export', 'PartnerController@export')->name('export');
            Route::get('/{id}/approve', 'PartnerController@approve')->name('approve');
            Route::get('/{id}/reject', 'PartnerController@reject')->name('reject');
            Route::get('/{id}', 'PartnerController@show')->name('show');
            Route::put('/{id}', 'PartnerController@update')->name('update');
            Route::get('/{id}/set-custom-rates', 'PartnerController@showCustomRatesForm')->name('show-custom-rates-form');
            Route::post('/{id}/set-custom-rates', 'PartnerController@saveCustomRates')->name('save-custom-rates');
            Route::post('/{id}/save-pricing', 'PartnerController@savePartnerPricing')->name('save-pricing');
            Route::post('/{id}/bulk-map-businesses', 'PartnerController@bulkMapBusinesses')->name('bulk-map-businesses');
        });

        Route::get('transfer/fast-payment', 'FastPaymentTransferController@index')->name('transfer.fast-payment.index');
        Route::post('transfer/fast-payment/export', 'FastPaymentTransferController@export')->name('transfer.fast-payment.export');
        Route::get('transfer/{transfer}', 'FastPaymentTransferController@get')->name('transfer.fast-payment.get');
        Route::put('transfer/{transfer}', 'FastPaymentTransferController@update')->name('transfer.fast-payment.update');
        Route::post('transfer/{transfer}', 'FastPaymentTransferController@trigger')->name('transfer.fast-payment.trigger');

        Route::get('campaigns', 'CashbackCampaignController@index')->name('campaigns.index');
        Route::get('campaigns/create', 'CashbackCampaignController@create')->name('campaigns.create');
        Route::post('campaigns/{cashback_campaign_id?}', 'CashbackCampaignController@store')->name('campaigns.store');
        Route::get('campaigns/{cashback_campaign_id}', 'CashbackCampaignController@edit')->name('campaigns.edit');
        Route::delete('campaigns/{cashback_campaign_id}', 'CashbackCampaignController@delete')->name('campaigns.delete');
        Route::post('campaigns/{cashback_campaign_id}/rule', 'CashbackCampaignController@addRule')->name('campaigns.add-rule');

        Route::get('business/verification-documents/{file_path}', 'BusinessController@downloadVerifyDoc')->name('verification-files.download');

        Route::get('onboarding', 'OnboardingController@index')->name('onboarding.index');
        Route::get('onboarding/{slug}', 'OnboardingController@provider')->name('onboarding.provider');
        Route::get('onboarding/{slug}/merchants', 'OnboardingController@merchantList')->name('onboarding.merchants');
        Route::get('onboarding/{slug}/download', 'OnboardingController@downloadCsv')->name('onboarding.download');
        Route::post('onboarding/{slug}/upload', 'OnboardingController@uploadCsv')->name('onboarding.upload');

        Route::prefix('business/{business_id}/bank-accounts')->name('business.bank_accounts.')->group(function () {
            Route::get('/', 'BusinessBankAccountController@index')->name('index');
        });

        Route::get('import-dbs-reconcile', 'ImportDbsReconcileController@index')->name('importdbsreconcile.index');
        Route::post('import-dbs-reconcile', 'ImportDbsReconcileController@store')->name('importdbsreconcile.store');
    });
});
