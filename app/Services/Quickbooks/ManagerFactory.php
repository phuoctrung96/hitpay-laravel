<?php


namespace App\Services\Quickbooks;


use App\Models\Business\QuickbookIntegration;
use Carbon\Carbon;
use QuickBooksOnline\API\DataService\DataService;

class ManagerFactory
{
    /**
     * @param QuickbookIntegration $quickbooksIntegration
     * @return AccountsManager
     */
    public static function makeAccountsManager(QuickbookIntegration $quickbooksIntegration): AccountsManager
    {
        return new AccountsManager(static::makeDataService($quickbooksIntegration));
    }

    public static function makeCustomerManager(QuickbookIntegration $quickbooksIntegration): CustomersManager
    {
        return new CustomersManager(static::makeDataService($quickbooksIntegration));
    }

    public static function makePaymentMethodManager(QuickbookIntegration $quickbooksIntegration): PaymentMethodsManager
    {
        return new PaymentMethodsManager(static::makeDataService($quickbooksIntegration));
    }

    /**
     * @param QuickbookIntegration $quickbooksIntegration
     * @return CompaniesManager
     */
    public static function makeCompaniesManager(QuickbookIntegration $quickbooksIntegration): CompaniesManager
    {
        return new CompaniesManager(static::makeDataService($quickbooksIntegration));
    }

    public static function makeDataService(QuickbookIntegration $quickbooksIntegration): DataService
    {
        if($quickbooksIntegration->accessTokenExpired()) {
            $quickbooksIntegration = self::refreshToken($quickbooksIntegration);
        }

        return app()->makeWith(DataService::class, [
            'accessToken' => $quickbooksIntegration->access_token,
            'refreshToken' => $quickbooksIntegration->refresh_token,
            'realmId' => $quickbooksIntegration->realm_id
        ]);
    }

    private static function refreshToken(QuickbookIntegration $quickbooksIntegration): QuickbookIntegration
    {
        $authService = resolve(AuthorizationService::class);
        $token = $authService->refreshToken($quickbooksIntegration->refresh_token);
        $quickbooksIntegration->update([
            'refresh_token' => $token->getRefreshToken(),
            'access_token' => $token->getAccessToken(),
            'access_token_expires_at' => Carbon::parse($token->getAccessTokenExpiresAt())
        ]);

        return $quickbooksIntegration->fresh();
    }
}
