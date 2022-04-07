<?php


namespace App\Services\Xero;


use App\Business;
use App\Notifications\XeroAccountDisconnectedNotification;
use App\Services\XeroApiFactory;
use Illuminate\Support\Facades\Log;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class DisconnectService
{
    public function disconnectBusinessFromXero(Business $business): void
    {
        $business->disconnectXero();
    }

    public function isXeroConnectionDead(Business $business): bool
    {
        try {
            XeroApiFactory::getAccessToken($business);
        } catch (IdentityProviderException $exception) {
            if($exception->getMessage() != 'invalid_grant') {
                return true;
            }

            Log::channel('xero')->emergency($exception);
        } catch (\Exception $exception) {
            Log::channel('xero')->emergency($exception);
        }

        return false;
    }
}
