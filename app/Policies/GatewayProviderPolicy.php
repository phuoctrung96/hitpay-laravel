<?php

namespace App\Policies;

use App\Business;
use App\Business\GatewayProvider;
use App\Enumerations\Permission;
use App\Services\BusinessUserPermissionsService;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GatewayProviderPolicy
{
    use HandlesAuthorization;

    /**
     * @var BusinessUserPermissionsService
     */
    private $businessUserPermissionsService;

    public function __construct(BusinessUserPermissionsService $businessUserPermissionsService)
    {
        $this->businessUserPermissionsService = $businessUserPermissionsService;
    }

    /**
     * Determine if a user can view a business.
     *
     * @param \App\User $user
     * @param \App\Business\GatewayProvider $gatewayProvider
     * @param \App\Business\Business $business
     *
     * @return \Illuminate\Auth\Access\Response
     */
    public function view(User $user, GatewayProvider $gatewayProvider, Business $business)
    {
        if($user->role->isSuperAdmin()) {
            return $this->allow();
        }

        switch (true) {
            case $gatewayProvider->business->user_id === $user->getKey():
            case $user->role && $user->role->hasPermission(Permission::ALL):
            case $gatewayProvider->business->getkey() === $business->getKey():
                return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine if a user can update api key.
     *
     * @param \App\User $user
     * @param \App\Business\GatewayProvider $gatewayProvider
     * @param \App\Business\Business $business
     *
     * @return \Illuminate\Auth\Access\Response
     */
    public function update(User $user, GatewayProvider $gatewayProvider, Business $business)
    {
        return $this->view($user, $gatewayProvider, $business);
    }

    /**
     * Determine if a user can update api key.
     *
     * @param \App\User $user
     * @param \App\Business\GatewayProvider $gatewayProvider
     * @param \App\Business\Business $business
     *
     * @return \Illuminate\Auth\Access\Response
     */
    public function delete(User $user, GatewayProvider $gatewayProvider, Business $business)
    {
        return $this->view($user, $gatewayProvider, $business);
    }
}
