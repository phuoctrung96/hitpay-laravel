<?php

namespace App\Policies;

use App\Business\ApiKey;
use App\Enumerations\Permission;
use App\Services\BusinessUserPermissionsService;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApiKeyPolicy
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
     * @param \App\Business\ApiKey $apiKey
     *
     * @return \Illuminate\Auth\Access\Response
     */
    public function view(User $user, ApiKey $apiKey)
    {
        switch ($businessUser = $this->businessUserPermissionsService->getBusinessUser($user, $apiKey->business)) {
            case $businessUser->isOwner():
            case $businessUser->isAdmin():
                return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine if a user can update api key.
     *
     * @param \App\User $user
     * @param \App\Business\ApiKey $apiKey
     *
     * @return \Illuminate\Auth\Access\Response
     */
    public function update(User $user, ApiKey $apiKey)
    {
        switch ($businessUser = $this->businessUserPermissionsService->getBusinessUser($user, $apiKey->business)) {
            case $businessUser->isOwner():
            case $businessUser->isAdmin():
                return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine if a user can delete api key.
     *
     * @param \App\User $user
     * @param \App\Business\ApiKey $apiKey
     *
     * @return \Illuminate\Auth\Access\Response
     */
    public function delete(User $user, ApiKey $apiKey)
    {
        switch ($businessUser = $this->businessUserPermissionsService->getBusinessUser($user, $apiKey->business)) {
            case $businessUser->isOwner():
            case $businessUser->isAdmin():
                return $this->allow();
        }

        return $this->deny();
    }
}
