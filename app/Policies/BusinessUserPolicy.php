<?php

namespace App\Policies;

use App\Business;
use App\Role;
use App\Services\BusinessUserPermissionsService;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BusinessUserPolicy
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

    public function list(User $executor, Business $business)
    {
        return $this->businessUserPermissionsService->can($executor, $business, 'canManageUsers')
            ? $this->allow()
            : $this->deny();
    }

    public function manage(User $executor, Business $business)
    {
        return $this->businessUserPermissionsService->can($executor, $business, 'canManageUsers')
            ? $this->allow()
            : $this->deny();
    }
}
