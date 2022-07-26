<?php

use App\Business;
use App\Services\Quickbooks\HitpaySalesExportService;
use App\Services\XeroSalesService;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use HitPay\Shopify\Shopify;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;

Route::get('quickbooks-test', function(HitpaySalesExportService $exportService) {
    return $exportService->export();
});

Route::namespace('Dashboard')->group(function () {

    Route::group(['middleware' => 'guest'], function() {
        Route::get('auth/{provider}', 'Auth\LoginController@redirectToProvider')->name('auth.socialite');
        Route::get('auth/{provider}/callback', 'Auth\LoginController@handleProviderCallback')->name('auth.socialite.callback');
        Route::get('xero-login', 'XeroLoginController@index')->name('xero.login');
    });


    Route::get('/', 'HomeController@showHomepage')->name('dashboard.home');

    Route::namespace('Auth')->group(function () {
        Route::get('auth', 'AuthenticationController@showEmailForm')->name('auth');
        Route::post('auth', 'AuthenticationController@searchAccount')->name('auth.process');
        Route::get('stripe/authenticate', 'AuthenticationController@authenticateStripeAccount')->name('stripe.authenticate');

        Route::get('checkpoint', 'LoginController@showCheckpointForm')->name('checkpoint');
        Route::post('checkpoint', 'LoginController@loginStep2')->name('checkpoint.process');
        Route::get('login', 'LoginController@showLoginForm')->name('login');
        Route::post('login', 'LoginController@loginStep1')->name('login.process');
        Route::post('logout', 'LoginController@logout')->name('logout');
        Route::post('password/email', 'ForgotPasswordController@sendResetLinkEmail')->name('password.email');
        Route::get('password/reset', 'ForgotPasswordController@showLinkRequestForm')->name('password.request');
        Route::post('password/reset', 'ResetPasswordController@reset')->name('password.update');
        Route::get('password/reset/{token}', 'ResetPasswordController@showResetForm')->name('password.reset');
        Route::get('register', 'RegisterController@showRegistrationForm')->name('register');
        Route::post('register', 'RegisterController@register')->name('register.process');
        Route::get('register-complete/{hash}', 'RegisterCompleteController@showRegistrationForm')->name('register-complete');
        Route::post('register-complete/{hash}', 'RegisterCompleteController@register')->name('register-complete.process');


        Route::get('register-partner', 'RegisterPartnerController@showRegistrationForm')->name('register-partner');
        Route::post('register-partner', 'RegisterPartnerController@register')->name('register-partner.process');

        Route::get('email/verify', 'VerificationController@show')->name('verification.notice');
        Route::get('email/verify/{id}/{hash}', 'VerificationController@verify')->name('verification.verify');
        Route::get('email/resend', 'VerificationController@resend')->name('verification.resend');

    });

    Route::name('dashboard.')->group(function () {
        Route::prefix('partner')->name('partner.')->middleware('auth', 'partner')->group(function () {
            Route::get('/', 'PartnerController@index')->name('index');
        });

        Route::prefix('pending-invitations')->name('pending-invitations.')->middleware('auth')->group(function () {
            Route::get('/', 'InvitationController@index')->name('index');
            Route::get('{id}/accept', 'InvitationController@accept')->name('accept');
            Route::get('{id}/decline', 'InvitationController@decline')->name('decline');
        });

        Route::get('choose-business', 'ChooseBusinessController')->name('choose-business');
        Route::post('help-guide', 'BusinessController@showHelpGuide')->name('help-guide');


        Route::prefix('business')->name('business.')->group(function () {
            Route::get('/', 'BusinessController@showBusinessCreationForm')->name('create');
            Route::get('create', 'BusinessController@showBusinessCreationForm')->name('create');
            Route::post('/', 'BusinessController@createBusiness')->name('store');
            Route::prefix('{business_id}')->group(function () {
                Route::get('/', 'BusinessController@show')->name('home');

                Route::prefix('referral-program')->name('referral-program.')->middleware('auth')->group(function () {
                    Route::get('/', 'BusinessReferralProgramController@index')->name('index');
                    Route::post('/', 'BusinessReferralProgramController@sendInvite')->name('send-invite');
                });

                Route::namespace('Business')->group(function () {
                    Route::prefix('user-management')->name('users.')->group(function() {
                        Route::get('/', 'UserController@index')->name('index');
                        Route::post('invite', 'UserController@invite')->name('invite');
                        Route::post('{id}/update', 'UserController@update')->name('update');
                        Route::get('{id}/detach', 'UserController@detach')->name('detach');
                    });

                    Route::prefix('balance')->name('balance.')->group(function () {
                        Route::get('/', 'BalanceController@showHomepage')->name('homepage');
                        Route::post('top-up-intent/{topUpIntentId}', 'BalanceController@getTopUpIntent')->name('wallet.top-up-intent');
                        Route::get('transactions/cards', 'BalanceController@showStripeTransactionsPage')->name('stripe.transactions');
                        Route::put('{currency}/available', 'BalanceController@updateAvailableWallet')->name('wallet.available.update');
                        Route::post('{currency}/available/payout', 'BalanceController@requestPayout')->name('wallet.available.payout');
                        Route::post('{currency}/available/top-up/intent', 'BalanceController@generateTopUpIntent')
                            ->name('wallet.top-up-intent.generate');
                        Route::put('{currency}/reserve', 'BalanceController@updateReserveWallet')->name('wallet.reserve.update');
                        Route::get('{currency}/transactions', 'BalanceController@showTransactionsPage')->name('transactions');
                        Route::get('{currency}/{type}', 'BalanceController@showWalletPage')->name('wallet');
                        Route::get('{currency}', 'BalanceController@showCurrencyPage')->name('currency');
                    });

                    Route::prefix('role-restrictions')->name('restrictions.')->group(function() {
                        Route::get('/', 'RoleRestrictionsController@index')->name('index');
                        Route::put('/', 'RoleRestrictionsController@update')->name('update');
                    });
                    Route::get('partners', function(\Illuminate\Http\Request $request, \App\Business $business){
                        return view('dashboard.business.partners.index',['business' => $business]);
                    });

                    Route::prefix('balance')->name('balance.')->group(function () {
                        Route::get('/', 'BalanceController@showHomepage')->name('homepage');
                        Route::post('top-up-intent/{topUpIntentId}', 'BalanceController@getTopUpIntent')->name('wallet.top-up-intent');
                        Route::put('{currency}/available', 'BalanceController@updateAvailableWallet')->name('wallet.available.update');
                        Route::post('{currency}/available/payout', 'BalanceController@requestPayout')->name('wallet.available.payout');
                        Route::post('{currency}/available/top-up/intent', 'BalanceController@generateTopUpIntent')
                            ->name('wallet.top-up-intent.generate');
                        Route::put('{currency}/reserve/{transferType}', 'BalanceController@updateReserveWallet')->name('wallet.reserve.update');
                        Route::get('{currency}/transactions', 'BalanceController@showTransactionsPage')->name('transactions');
                        Route::get('{currency}/{type}', 'BalanceController@showWalletPage')->name('wallet');
                        Route::get('{currency}', 'BalanceController@showCurrencyPage')->name('currency');
                    });

                    Route::prefix('charge')->name('charge.')->group(function () {
                        Route::get('/', 'ChargeController@index')->name('index');
                        Route::post('export', 'ChargeController@export')->name('export');
                        Route::get('{b_charge}', 'ChargeController@show')->name('show');
                        Route::put('{b_charge}', 'ChargeController@update')->name('update');
                        Route::delete('{b_charge}', 'ChargeController@delete')->name('delete');
                        Route::post('{b_charge}/receipt', 'ChargeController@receipt')->name('receipt');
                        Route::post('{b_charge}/refund', 'ChargeController@refund')->name('refund');
                        Route::post('{b_charge}/paynow/refund', 'ChargeController@payNowRefund')->name('paynow.refund');
                        Route::post('{b_charge}/wallet/refund', 'ChargeController@payNowRefund')->name('wallet.refund');
                        Route::post('{b_charge}/paynow/refund/{b_refund}', 'ChargeController@getRefundStatus')->name('paynow.refund.status');
                        Route::get('{b_charge}/canrefund', 'ChargeController@canRefund')->name('canrefund');
                    });

                    Route::prefix('invoice')->name('invoice.')->group(function () {
                        Route::get('/', 'InvoiceController@index')->name('index');
                        Route::post('/', 'InvoiceController@store')->name('store');
                        Route::get('new', 'InvoiceController@create')->name('create');
                        Route::get('{b_invoice}/detail', 'InvoiceController@detail')->name('detail');
                        Route::get('/create-in-bulk', 'InvoiceController@createInBulk')->name('bulk');
                        Route::get('download-feed-template', 'InvoiceController@downloadFeedTemplate')->name('download-feed-template');
                        Route::post('upload-feed-file', 'InvoiceController@uploadFeedFile')->name('upload-feed-file');
                        Route::get('{b_invoice}', 'InvoiceController@show')->name('show');
                        Route::get('{b_invoice}/edit', 'InvoiceController@edit')->name('edit');
                        Route::post('{b_invoice}/resend', 'InvoiceController@resend')->name('resend');
                        Route::post('{b_invoice}/delete', 'InvoiceController@delete')->name('delete');
                        Route::post('{b_invoice}/remind', 'InvoiceController@remind')->name('send.remind');
                        Route::post('{b_invoice}/save', 'InvoiceController@save')->name('save.undraft');
                        Route::post('{b_invoice}/print', 'InvoiceController@print')->name('print');
                    });

                    Route::prefix('payment-links')->name('payment-links.')->group(function () {
                        Route::get('/', 'PaymentLinkController@index')->name('index');
                        Route::post('/', 'PaymentLinkController@store')->name('store');
                        Route::get('{payment_request_id}/delete', 'PaymentLinkController@delete')->name('delete');
                    });

                    Route::prefix('customer')->name('customer.')->group(function () {
                        Route::get('/', 'CustomerController@index')->name('index');
                        Route::post('/', 'CustomerController@store')->name('store');
                        Route::get('create', 'CustomerController@create')->name('create');
                        Route::post('export', 'CustomerController@export')->name('export');
                        Route::get('create-in-bulk', 'CustomerController@createInBulk')->name('bulk');
                        Route::get('download-feed-template', 'CustomerController@downloadFeedTemplate')->name('download-feed-template');
                        Route::post('upload-feed-file', 'CustomerController@uploadFeedFile')->name('upload-feed-file');
                        Route::get('{b_customer}', 'CustomerController@show')->name('show');
                        Route::put('{b_customer}', 'CustomerController@update')->name('update');
                        Route::delete('{b_customer}', 'CustomerController@destroy')->name('delete');
                        Route::post('delete-bulk', 'CustomerController@bulkDestroy')->name('delete-bulk');
                        Route::get('{b_customer}/edit', 'CustomerController@edit')->name('edit');
                    });

                    Route::prefix('order')->name('order.')->group(function () {
                        Route::get('/', 'OrderController@index')->name('index');
                        // Route::get('/{pending}', 'OrderController@index')->name('index');
                        Route::post('export', 'OrderController@export')->name('export');
                        Route::post('delivery-report', 'OrderController@deliveryReportExport')->name('delivery.export');
                        Route::post('update', 'OrderController@markAsCompleted')->name('update-orders');
                        Route::get('cancel/{b_order}/{refund}', 'OrderController@cancel')->name('cancel');
                        Route::post('cancel-order-requires-customer-action/{b_order}', 'CancelOrderRequiresCustomerActionController')->name('cancel.requires_customer_action');
                        Route::get('{b_order}', 'OrderController@show')->name('show');
                        Route::put('{b_order}', 'OrderController@update')->name('update');
                        Route::put('{b_order}/reference', 'OrderController@updateReference')->name('update.reference');
                        Route::delete('{b_order}', 'OrderController@delete')->name('delete');
                    });
                    Route::prefix('discount')->name('discount.')->group(function () {
                        Route::resource('/', 'DiscountController', [
                            'names' => [
                                'index' => 'home',
                                'create' => 'create',
                            ]
                        ]);
                        Route::get('{discount}/edit', 'DiscountController@edit')->name('edit');
                    });
                    Route::prefix('coupon')->name('coupon.')->group(function () {
                        Route::resource('/', 'CouponController', [
                            'names' => [
                                'index' => 'home',
                                'create' => 'create',
                            ]
                        ]);
                        Route::get('{coupon}/edit', 'CouponController@edit')->name('edit');
                    });

                    Route::prefix('setting')->name('setting.')->group(function () {
                        Route::get('/', 'BusinessSettingsController@index')->name('index');
                        Route::post('/', 'BusinessSettingsController@store')->name('store');
                        Route::put('/{b_setting}', 'BusinessSettingsController@update')->name('update');

                        Route::prefix('shipping')->name('shipping.')->group(function () {
                            Route::post('discount', 'ShippingController@storeDiscount')->name('create-discount');
                            Route::get('discount/{b_shipping_discount}', 'ShippingController@deleteDiscount')->name('delete-discount');
                            Route::get('/', 'ShippingController@index')->name('home');
                            Route::post('/', 'ShippingController@store')->name('store');
                            Route::get('create', 'ShippingController@create')->name('create');
                            Route::get('{b_shipping}', 'ShippingController@edit')->name('edit');
                            Route::put('{b_shipping}', 'ShippingController@update')->name('update');
                            Route::delete('{b_shipping}', 'ShippingController@destroy')->name('delete');
                        });

                        Route::prefix('shop')->name('store.')->group(function () {
                            Route::get('/', 'StoreSettingsController@index')->name('home');
                            Route::put('update', 'StoreSettingsController@updateInformation')->name('information');
                            Route::post('slots', 'StoreSettingsController@saveSlots')->name('save-slots');
                            Route::post('cover-image', 'StoreSettingsController@uploadCoverImage')->name('cover');
                            Route::delete('cover-image', 'StoreSettingsController@removeCoverImage')->name('cover-image.remove');
                        });
                    });

                    Route::prefix('basic-details')->name('basic-details.')->group(function () {
                        Route::get('/', 'BasicDetailController@index')->name('home');
                        Route::put('identifier', 'BasicDetailController@updateIdentifier')->name('identifier');
                        Route::put('information', 'BasicDetailController@updateInformation')->name('information');
                        Route::post('slug', 'BasicDetailController@updateSlug')->name('slug');
                        Route::post('logo', 'BasicDetailController@uploadLogo')->name('logo');
                        Route::post('tax-details', 'BasicDetailController@updateTaxDetails')->name('update-tax-details');
                        Route::get('fb-feed-url', 'BasicDetailController@createFacebookFeedUrl')->name('fb-feed-url');
                        Route::delete('logo', 'BasicDetailController@removeLogo')->name('logo.remove');
                    });

                    Route::prefix('notifications')->name('notifications')->group(function () {
                        Route::get('/', 'NotificationController@index')->name('index');
                        Route::put('/', 'NotificationController@update')->name('update');
                    });

                    Route::prefix('fee-invoices')->name('fee-invoices.')->group(function () {
                        Route::get('/', 'TaxInvoiceController@index')->name('index');
                        Route::post('/download', 'TaxInvoiceController@downloadInvoice')->name('index');
                    });

                    Route::prefix('integration/shopify')->name('integration.shopify.')->group(function () {
                        Route::get('/', 'ShopifyController@showHomepage')->name('home');
                        Route::get('redirect', 'ShopifyController@doRedirection')->name('redirect');
                        Route::get('authorize', 'ShopifyController@authorizeAccount')->name('authorize');
                        Route::delete('unauthorize', 'ShopifyController@unauthorize')->name('unauthorize');

                        Route::prefix('setting')->name('setting.')->group(function () {
                            Route::get('location', 'ShopifyController@showLocationSettingPage')->name('location');
                            Route::post('location', 'ShopifyController@setLocation')->name('location.set');
                            Route::get('product', 'ShopifyController@showProductSyncPage')->name('product');
                            Route::post('product', 'ShopifyController@syncProduct')->name('product.sync');
                            Route::get('product/sync', 'ShopifyController@getProductSyncProgress')
                                ->name('product.sync.progress');
                            Route::get('store', 'ShopifyController@showHome')->name('store');
                        });
                    });

                    Route::prefix('payment-integration/shopify')->name('payment.integration.shopify.')->group(function () {
                        Route::get('/', 'ShopifyPaymentController@index')->name('index');
                        Route::get('authorize', 'ShopifyPaymentController@authorizeAccount')->name('authorize');
                        Route::get('/confirm', 'ShopifyPaymentController@confirm')->name('confirm');
                    });

                    Route::namespace('Shopify')->group(function () {
                        Route::prefix('shopify-payment-app')->name('shopify-payment-app.')->group(function () {
                            Route::get('/', 'ShopifyStoreController@index')->name('store.index');

                            Route::delete('{b_shopify_store}', 'ShopifyStoreController@destroy')->name('store.destroy');
                        });
                    });

                    Route::prefix('payment-provider/paynow')->name('payment-provider.paynow.')->group(function () {
                        Route::get('home', 'PayNowController@showHomepage')->name('homepage');
                        Route::post('/', 'PayNowController@setup')->name('setup');
                        Route::get('payout', 'PayNowController@payout')->name('payout');
                        Route::get('payout/{b_transfer}', 'PayNowController@payoutShow')->name('payout.show');
                        Route::get('payout/{b_transfer}/download', 'PayNowController@downloadPayoutDetails')->name('payout.download');
                        Route::post('payout-breakdown/export', 'PayNowController@exportBreakdown')->name('export.breakdown');
                        Route::post('payout/export', 'PayNowController@export')->name('export');
                    });

                    Route::prefix('tax-setting')->name('tax-setting.')->group(function () {
                        Route::get('/', 'TaxSettingController@index')->name('home');
                        Route::post('/', 'TaxSettingController@store')->name('store');
                        Route::post('/{b_tax_setting}/update', 'TaxSettingController@update')->name('update');
                        Route::get('/{b_tax_setting}/edit', 'TaxSettingController@edit')->name('edit');
                        Route::get('/{b_tax_setting}/delete', 'TaxSettingController@delete')->name('delete');
                    });

                    Route::prefix('platform')->name('platform.')->group(function () {
                        Route::get('/', 'PlatformController@index')->name('index');
                        Route::put('/', 'PlatformController@update')->name('update');
                        Route::post('charge/export', 'PlatformController@exportCharge')->name('charge.export');
                        Route::get('payout', 'PlatformController@payout')->name('payout');
                        Route::get('payout/{b_commission}', 'PlatformController@payoutShow')->name('payout.show');
                        Route::post('payout/export', 'PlatformController@export')->name('payout.export');
                        Route::put('rekey', 'PlatformController@rekey')->name('rekey');
                    });

                    Route::prefix('recurring-plan')->name('recurring-plan.')->group(function () {
                        Route::get('/', 'RecurringBillingController@index')->name('index');
                        Route::post('/', 'RecurringBillingController@store')->name('store');
                        Route::get('new', 'RecurringBillingController@create')->name('create');
                        Route::get('new/template', 'RecurringBillingController@createWithTemplate')->name('create-template');
                        Route::prefix('template')->name('template.')->group(function () {
                            Route::get('/', 'SubscriptionPlanController@index')->name('index');
                            Route::post('/', 'SubscriptionPlanController@update')->name('store');
                            Route::get('new', 'SubscriptionPlanController@edit')->name('create');
                            Route::get('{b_subscription_plan}/edit', 'SubscriptionPlanController@edit')->name('edit');
                            Route::put('{b_subscription_plan}', 'SubscriptionPlanController@update')->name('update');
                            Route::delete('{b_subscription_plan}', 'SubscriptionPlanController@destroy')->name('destroy');
                        });
                        Route::get('{b_recurring_billings}', 'RecurringBillingController@show')->name('show');
                        Route::post('{b_recurring_billings}/send', 'RecurringBillingController@sendLink')->name('send');
                        Route::get('{b_recurring_billings}/edit', 'RecurringBillingController@edit')->name('edit');
                        Route::put('{b_recurring_billings}', 'RecurringBillingController@update')->name('update');
                        Route::delete('{b_recurring_billings}', 'RecurringBillingController@cancel')->name('cancel');
                    });

                    Route::prefix('integration/xero')->middleware('auth')->name('integration.xero.')->group(function () {
                        Route::get('/login', 'XeroController@xeroAuthorize')->name('login');
                        Route::get('/home', 'XeroController@index')->name('home');
                        Route::post('save-settings', 'XeroController@saveSettings')->name('save-settings');
                        Route::get('disconnect', 'XeroController@disconnect')->name('disconnect');
                    });

                    Route::get('payment-provider', 'PaymentProviderController@showHomePage')->name('payment-provider.home');

                    Route::prefix('payment-provider/paynow')->name('payment-provider.paynow.')->group(function () {
                        Route::get('/', 'PayNowController@showHomepage')->name('homepage');
                        Route::post('/', 'PayNowController@setup')->name('setup');
                        Route::get('payout', 'PayNowController@payout')->name('payout');
                        Route::get('payout/{b_transfer}', 'PayNowController@payoutShow')->name('payout.show');
                        Route::post('payout/export', 'PayNowController@export')->name('export');
                    });

                    Route::prefix('integration/quickbooks')->middleware('auth')->name('integration.quickbooks.')->group(function () {
                        Route::get('login', 'QuickbooksController@quickbooksAuthorize')->name('login');
                        Route::get('home', 'QuickbooksController@index')->name('home');
                        Route::post('save-settings', 'QuickbooksController@saveSettings')->name('save-settings');
                        Route::get('disconnect', 'QuickbooksController@disconnect')->name('disconnect');
                    });


                    Route::prefix('email-templates')->middleware('auth')->name('email-templates')->group(function () {
                        Route::get('/', 'EmailTemplateController@index')->name('home');
                    });

                    Route::prefix('integration/hotglue')->middleware('auth')->name('integration.hotglue.')->group(function () {
                        Route::get('home', 'HotglueController@index')->name('home');
                        Route::post('source-connected', 'HotglueController@sourceConnected')->name('connected');
                        Route::put('source-disconnected', 'HotglueController@sourceDisconnected')->name('disconnected');
                        Route::post('target-linked', 'HotglueController@targetLinked')->name('target-linked');
                        Route::put('product-periodic-sync', 'HotglueController@productPeriodicSync')->name('product-periodic-sync');
                        Route::put('sync-all-hitpay-orders', 'HotglueController@syncAllHitpayOrders')->name('sync-all-hitpay-orders');
                        Route::post('sync-now', 'HotglueController@syncNow')->name('sync-now');
                        Route::put('inventory-location', 'HotglueController@inventoryLocation')->name('inventory-location');
                    });

                    Route::namespace('Stripe')->prefix('payment-provider/stripe')->name('payment-provider.stripe.')
                        ->group(function () {
                            Route::get('/', 'PaymentProviderController@showHomepage')->name('home');
                            Route::delete('/', 'PaymentProviderController@deauthorizeAccount')->name('deauthorize');
                            Route::get('authorize', 'PaymentProviderController@authorizeAccount')->name('authorize');
                            Route::get('redirect', 'PaymentProviderController@doRedirection')->name('redirect');
                            Route::get('payout', 'PayoutController')->name('payout'); // to hit-pay payouts for MY
                            Route::get('payout-custom', 'PayoutCustomController@showPage')->name('payout.custom'); // for SG
                            Route::get('payout-custom/{b_transfer}/download', 'PayoutCustomController@download')
                                ->name('payout.custom.download');
                            Route::get('payout-standard', 'PayoutStandardController@index')->name('payout.standard');

                            Route::get('onboard-verification', 'OnboardVerificationController@show')
                                ->name('onboard-verification');

                            Route::post('onboard-verification', 'OnboardVerificationController@store')
                                ->name('onboard-verification.store');

                            Route::post('connect-onboarding', 'ConnectOnboardingController@store')
                                ->name('connect-onboarding.store');
                        });

                    Route::namespace('Shopee')->prefix('payment-provider/shopee')
                        ->group(function () {
                            Route::get('/', 'ShopeeController@showHomepage');
                            Route::post('/', 'ShopeeController@setShopeeStatus');
                            Route::post('/remove', 'ShopeeController@deauthorizeAccount');
                        });

                    Route::namespace('Hoolah')->prefix('payment-provider/hoolah')
                        ->group(function () {
                            Route::get('/', 'HoolahController@showHomepage');
                            Route::post('/', 'HoolahController@setHoolahStatus');
                            Route::post('/remove', 'HoolahController@deauthorizeAccount');
                        });

                    Route::namespace('GrabPay')->prefix('payment-provider/grabpay')
                        ->group(function () {
                            Route::get('/', 'GrabPayController@showHomepage');
                            Route::post('/', 'GrabPayController@setGrabPayStatus');
                            Route::post('/remove', 'GrabPayController@deauthorizeAccount');
                        });

                    Route::namespace('Zip')->prefix('payment-provider/zip')
                        ->group(function () {
                            Route::get('/', 'ZipController@showHomepage');
                            Route::post('/', 'ZipController@setZipStatus');
                            Route::post('/remove', 'ZipController@deauthorizeAccount');
                        });

                    Route::prefix('point-of-sale')->name('point-of-sale.')->group(function () {
                        Route::get('/', 'PointOfSaleController@showHomepage')->name('home');
                        Route::post('charge', 'PointOfSaleController@createCharge')->name('charge');
                        Route::get('charge/{b_charge}', 'PointOfSaleController@getCharge')->name('charge.show');
                        Route::post('charge/{b_charge}/cash', 'PointOfSaleController@logCash')->name('charge.cash');
                        Route::post('charge/{b_charge}/link', 'PointOfSaleController@logLink')->name('charge.link');
                        Route::delete('charge/{b_charge}', 'PointOfSaleController@cancelCharge')->name('charge.cancel');
                        Route::get('charge/{b_charge}/alipay/callback', 'PointOfSaleController@showAlipayStatus')
                            ->name('charge.alipay.callback');
                        Route::post('charge/{b_charge}/payment-intent',
                            'PointOfSaleController@createPaymentIntentForCharge')->name('charge.payment-intent');
                        Route::post('connection_token', 'PointOfSaleController@getConnectionToken')
                            ->name('connection_token');
                        Route::post('customer', 'PointOfSaleController@searchCustomer')->name('customer');
                        Route::post('discounts', 'PointOfSaleController@searchDiscounts')->name('discounts');
                        Route::post('order', 'PointOfSaleController@createOrder')->name('order.store');
                        Route::post('order/{b_order}/customer', 'PointOfSaleController@addCustomerToOrder')
                            ->name('order.customer.store');
                        Route::delete('order/{b_order}/customer', 'PointOfSaleController@removeCustomerFromOrder')
                            ->name('order.customer.delete');
                        Route::post('order/{b_order}/discount', 'PointOfSaleController@setDiscount')
                            ->name('order.discount');
                        Route::post('order/{b_order}/tax-setting', 'PointOfSaleController@setTaxSetting')
                            ->name('order.tax_setting');
                        Route::post('order/{b_order}/product', 'PointOfSaleController@addProductToOrder')
                            ->name('order.product.store');
                        Route::put('order/{b_order}/product/{b_ordered_product}',
                            'PointOfSaleController@updateProductInOrder')->name('order.product.update');
                        Route::delete('order/{b_order}/product/{b_ordered_product}',
                            'PointOfSaleController@deleteProductFromOrder')->name('order.product.delete');
                        Route::post('order/{b_order}/checkout', 'PointOfSaleController@checkoutOrder')
                            ->name('order.checkout');

                        Route::get('payment-intent/{id}', 'PointOfSaleController@getPaymentIntent')
                            ->name('payment-intent');
                        Route::post('payment-intent/{id}', 'PointOfSaleController@capturePaymentIntent')
                            ->name('payment-intent.capture');
                        Route::put('payment-intent/{id}', 'PointOfSaleController@confirmPaymentIntent')
                            ->name('payment-intent.confirm');
                        Route::post('product', 'PointOfSaleController@searchProduct')->name('product');
                        Route::post('category/{category_id}', 'PointOfSaleController@getProductWithCategory')->name('.getProductCategory');
                    });

                    Route::prefix('dashboard')->name('dashboard.')->group(function () {
                        Route::get('/', 'ShopDashboardController@index')->name('index');
                    });

                    Route::prefix('insight')->name('insight.')->group(function () {
                        Route::get('/', 'ShopDashboardController@insight')->name('insight.index');
                    });

                    Route::prefix('product')->name('product.')->group(function () {
                        Route::get('/', 'ProductController@index')->name('index');
                        Route::post('/', 'ProductController@store')->name('store');
                        Route::get('create', 'ProductController@create')->name('create');
                        Route::post('export', 'ProductController@export')->name('export');
                        Route::get('create-in-bulk', 'ProductController@createInBulk')->name('bulk');
                        Route::get('download-feed-template', 'ProductController@downloadFeedTemplate')->name('download-feed-template');
                        Route::post('upload-feed-template', 'ProductController@uploadFeedFile')->name('upload-feed-template');
                        Route::post('duplicate/{b_product}', 'ProductController@duplicate')->name('duplicate');
                        Route::get('{b_product}', 'ProductController@show')->name('show');
                        Route::put('{b_product}', 'ProductController@update')->name('update');
                        Route::delete('{b_product}', 'ProductController@delete')->name('delete');
                        Route::post('delete-products', 'ProductController@deleteProducts')->name('delete-products');
                        Route::get('{b_product}/edit', 'ProductController@edit')->name('edit');
                        Route::post('{b_product}/image', 'ProductController@addImage')->name('image.add');
                        Route::delete('{b_product}/image/{b_product_image}', 'ProductController@deleteImage')
                            ->name('image.delete');
                        Route::post('{b_product}/variation', 'ProductController@addVariation')->name('variation.add');
                        Route::delete('{b_product}/variation/{b_product_variation}', 'ProductController@deleteVariation')
                            ->name('variation.delete');
                    });
                    Route::prefix('product-categories')->name('product-categories.')->group(function () {
                        Route::get('/', 'ProductCategoryController@index')->name('index');
                        Route::post('/', 'ProductCategoryController@store')->name('store');
                        Route::get('create', 'ProductCategoryController@create')->name('create');
                        Route::put('{b_product_category}', 'ProductCategoryController@update')->name('update');
                        Route::get('{b_product_category}', 'ProductCategoryController@delete')->name('delete');
                        Route::get('{b_product_category}/edit', 'ProductCategoryController@edit')->name('edit');
                    });

                    Route::prefix('cashback')->name('cashback.')->group(function () {
                        Route::resource('/', 'CashbackController', [
                            'names' => [
                                'index' => 'index',
                                'store' => 'store',
                                'create' => 'create',
                            ]
                        ]);
                        Route::get('{b_cashback}/edit', 'CashbackController@edit')->name('edit');
                        Route::get('{b_cashback}/delete', 'CashbackController@delete')->name('delete');
                        Route::get('{b_cashback}/changeState/{enabled}', 'CashbackController@changeState')->name('change-state');
                    });

                    Route::prefix('apikey')->name('apikey.')->group(function () {
                        Route::get('/', 'ApiKeyController@index')->name('index');
                        Route::get('/create', 'ApiKeyController@create')->name('create');
                        Route::get('/{api_key_id}/delete', 'ApiKeyController@destroy')->name('delete');
                        Route::get('/{api_key_id}/change-status', 'ApiKeyController@changeStatus')->name('changeStatus');
                    });

                    Route::prefix('client-key')->name('oauth-client.')->group(function () {
                        Route::get('/', 'OauthClientController@index')->name('index');
                    });

                    Route::prefix('gateway-provider')->name('gateway.')->group(function () {
                        Route::get('/', 'GatewayProviderController@index')->name('index');
                        Route::get('/create', 'GatewayProviderController@create')->name('create');
                        Route::post('/', 'GatewayProviderController@store')->name('store');
                        Route::get('/{gateway_provider_id}', 'GatewayProviderController@show')->name('show');
                        Route::get('/{gateway_provider_id}/edit', 'GatewayProviderController@edit')->name('edit');
                        Route::put('/{gateway_provider_id}', 'GatewayProviderController@update')->name('update');
                        Route::get('/{gateway_provider_id}/delete', 'GatewayProviderController@destroy')->name('delete');
                    });

                    Route::prefix('customisation')->name('customisation.')->group(function () {
                      Route::get('/', 'CustomisationController@index')->name('index');
                      Route::patch('/', 'CustomisationController@patch')->name('patch');
                      Route::get('/rates', 'CustomisationController@getRateForAmount')->name('getRateForAmount');
                    });

                    Route::prefix('verification')->name('verification.')->group(function () {
                        Route::get('/', 'VerificationController@showHomepage')->name('home');
                        Route::get('{b_verification}/confirm', 'VerificationController@showConfirmPage')->name('confirm.page');
                        Route::post('confirm/{b_verification?}', 'VerificationController@confirm')->name('confirm');
                        Route::post('delete/{b_verification}', 'VerificationController@delete')->name('delete');
                        Route::get('{type}/redirect', 'VerificationController@redirect')->name('redirect');
                        Route::get('manual/{type?}', 'ManualVerificationController@create')->name('manual');
                        Route::post('manual', 'ManualVerificationController@store')->name('manual.store');

                        Route::post('more_confirm/{b_verification?}', 'VerificationMoreConfirmController@store')->name('more_confirm');

                        Route::prefix('cognito')->name('cognito.')->group(function () {
                            Route::get('/', 'VerificationCognitoController@index')->name('index');
                            Route::get('/{b_verification}', 'VerificationCognitoController@show')->name('show');
                            Route::post('/{b_verification}', 'VerificationCognitoController@store')->name('store');
                        });
                    });

                    Route::namespace('Onboard')->prefix('onboard')->name('onboard.')->group(function() {
                        Route::prefix('paynow')->name('paynow.')->group(function () {
                            Route::get('/', 'PaynowController@create')->name('create');
                            Route::post('/', 'PaynowController@store')->name('store');
                        });
                    });

                    Route::prefix('payouts')->name('payouts.')->group(function () {
                        Route::get('/', 'PayoutController@index')->name('index');
                    });
                });
            });

            Route::prefix('{business_id}')->group(base_path('routes/web/dashboard/business.php'));

            Route::prefix('verification')->name('verification.')->namespace('Business')->group(function () {
                Route::get('callback', 'VerificationController@callback')->name('callback');
            });
        });

        Route::get('payment-provider/stripe/callback', 'Business\Stripe\PaymentProviderController@callback')
            ->name('stripe.payment-provider.callback');

        Route::prefix('integration/shopify')->namespace('Business')->name('integration.shopify.')->group(function () {
            Route::get('/', 'ShopifyController@showHomepage')->name('home');
            Route::get('redirect', 'ShopifyController@selectBusiness')->name('business.select');
            Route::get('authorize', 'ShopifyController@doAuthorizationRedirection')->name('authorize');
        });

        Route::prefix('payment-integration/shopify')->namespace('Business')->name('payment.integration.shopify.')->group(function () {
            Route::get('/', 'ShopifyPaymentController@index')->name('index');
            Route::get('redirect', 'ShopifyOauthController@redirect')->name('redirect');
            Route::get('authorize', 'ShopifyOauthController@doAuthorizationRedirection')->name('authorize');
            Route::get('invalid-state', 'ShopifyOauthController@invalidState')->name('invalid.state');
        });

        Route::prefix('integration/xero')->namespace('Business')->name('integration.xero.')->group(function () {
            Route::get('callback', 'XeroController@handleCallBack')->name('callback');
        });
        Route::prefix('integration/quickbooks')->namespace('Business')->name('integration.quickbooks.')->group(function () {
            Route::get('callback', 'QuickbooksController@handleCallBack')->name('handle-callback');
        });

        Route::prefix('user')->name('user.')->group(function () {
            Route::get('welcome', 'UserController@showMigratedMessage')->name('welcome');
            Route::get('profile', 'UserController@showProfilePage')->name('profile');
            Route::put('profile', 'UserController@updateBasicInformation')->name('profile.update');
            Route::post('setup', 'UserController@setupAccount')->name('profile.setup');

            Route::prefix('security')->name('security.')->group(function () {
                Route::get('/', 'SecurityController@showHomepage')->name('home');
                Route::get('auth-secret', 'Auth\AuthSecretController@showSetUpPage')->name('secret');
                Route::post('auth-secret/code', 'Auth\AuthSecretController@secret')->name('secret.code');
                Route::post('auth-secret', 'Auth\AuthSecretController@enable')->name('secret.enable');
                Route::delete('auth-secret', 'Auth\AuthSecretController@disable')->name('secret.disable');

                Route::get('email', 'SecurityController@showEmailForm')->name('email.edit');
                Route::put('email', 'SecurityController@updateEmail')->name('email.update');
                Route::get('failed-auth', 'SecurityController@getFailedAuthRecords')->name('failed-auth');
                Route::get('password', 'SecurityController@showPasswordForm')->name('password.edit');
                Route::put('password', 'SecurityController@updatePassword')->name('password.update');
                Route::get('session', 'SecurityController@getSessionRecords')->name('session');
                Route::delete('session/{id}', 'SecurityController@destroySession')->name('session.destroy');
            });
        });
    });
});
