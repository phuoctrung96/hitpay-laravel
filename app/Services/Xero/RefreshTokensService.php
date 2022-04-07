<?php


namespace App\Services\Xero;


use App\Business;
use App\Manager\BusinessManagerInterface;
use App\Notifications\XeroAccountDisconnectedNotification;
use App\Services\XeroApiFactory;
use Exception;
use Illuminate\Support\Collection;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessTokenInterface;

class RefreshTokensService
{
    /**
     * @var BusinessManagerInterface
     */
    private $businessManager;
    /**
     * @var DisconnectService
     */
    private $disconnectService;

    public function __construct(BusinessManagerInterface $businessManager, DisconnectService $disconnectService)
    {
        $this->businessManager = $businessManager;
        $this->disconnectService = $disconnectService;
    }

    public function handle(): void
    {
        /** @var Business $business */
        foreach($this->businessManager->getBusinessesConnectedToXero() as $business) {
            if($this->disconnectService->isXeroConnectionDead($business)) {
                $this->disconnectService->disconnectBusinessFromXero($business);
            }
        }
    }
}
