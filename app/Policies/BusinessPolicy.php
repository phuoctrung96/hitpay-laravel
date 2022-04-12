<?php

namespace App\Policies;

use App\Business;
use App\Business\PaymentProvider;
use App\Enumerations\Permission;
use App\Services\BusinessUserPermissionsService;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BusinessPolicy
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
     * Determine if a user can create a business.
     *
     * @param \App\User $executor
     * @param \App\User|null $user
     *
     * @return \Illuminate\Auth\Access\Response
     */
    public function store(User $executor, User $user = null)
    {
        if($executor->role->isSuperAdmin()) {
            return $this->allow();
        }

        if ($user instanceof User) {
            if ($executor->role && $executor->role->hasPermission(Permission::ALL)) {
                if ($user->businessesOwned()->count() < 1) {
                    return $this->allow();
                }
            }
            return $this->deny();
        }

        if ($executor->businessesOwned()->count() < 1) {
            return $this->allow();
        }

        return $this->deny('You already have an active session in this browser, please logout and try again');
    }

    /**
     * Determine if a user can view a business.
     *
     * @param \App\User $user
     * @param \App\Business $business
     *
     * @return \Illuminate\Auth\Access\Response
     */
    public function view(User $user, Business $business)
    {
        if($user->role->isSuperAdmin()) {
            return $this->allow();
        }

        return $business->businessUsers()->where('user_id', $user->id)->exists()
            ? $this->allow()
            : $this->deny();
    }

    /**
     * Determine if a user can update a business.
     *
     * @param \App\User $user
     * @param \App\Business $business
     *
     * @return \Illuminate\Auth\Access\Response
     */
    public function update(User $user, Business $business)
    {
        if($user->role->isSuperAdmin()) {
            return $this->allow();
        }
        return $this->businessUserPermissionsService->can($user, $business, 'canUpdateBusiness')
            ? $this->allow()
            : $this->deny();
    }

    /**
     * Determine if a user can manage a business.
     *
     * @param \App\User $user
     * @param \App\Business $business
     *
     * @return \Illuminate\Auth\Access\Response
     */
    public function manage(User $user, Business $business)
    {
        if($user->role->isSuperAdmin()) {
            return $this->allow();
        }
        return $this->businessUserPermissionsService->can($user, $business, 'canManageBusiness')
            ? $this->allow()
            : $this->deny();
    }

    /**
     * Determine if a user can operate a business.
     *
     * @param \App\User $user
     * @param \App\Business $business
     *
     * @return \Illuminate\Auth\Access\Response
     */
    public function operate(User $user, Business $business)
    {
        if($user->role->isSuperAdmin()) {
            return $this->allow();
        }
        return $this->businessUserPermissionsService->can($user, $business, 'canOperatePointOfSale')
            ? $this->allow()
            : $this->deny();
    }

    public function canManageWallets(User $user, Business $business)
    {
        if ($user->role->isSuperAdmin()) {
            return $this->allow();
        }

        return $this->businessUserPermissionsService->can($user, $business, 'canManageWallets')
            ? $this->allow()
            : $this->deny();
    }

    public function canSendBalanceToBank(User $user, Business $business)
    {
        if ($user->role->isSuperAdmin()) {
            return $this->allow();
        }

        return $this->businessUserPermissionsService->can($user, $business, 'canSendBalanceToBank')
            ? $this->allow()
            : $this->deny();
    }

    /**
     * Determine if a user can deactivate a business.
     *
     * @param \App\User $user
     * @param \App\Business $business
     *
     * @return \Illuminate\Auth\Access\Response
     */
    public function deactivate(User $user, Business $business)
    {
        if($user->role->isSuperAdmin()) {
            return $this->allow();
        }
        return $this->businessUserPermissionsService->can($user, $business, 'canDeactivateBusiness')
            ? $this->allow()
            : $this->deny();
    }

    /**
     * Determine if a user can reactivate a business.
     *
     * @param \App\User $user
     * @param \App\Business $business
     *
     * @return \Illuminate\Auth\Access\Response
     */
    public function reactivate(User $user, Business $business)
    {
        if($user->role->isSuperAdmin()) {
            return $this->allow();
        }
        return $this->businessUserPermissionsService->can($user, $business, 'canReactivateBusiness')
            ? $this->allow()
            : $this->deny();
    }

    /**
     * Determine if a user can ban a business.
     *
     * @param \App\User $user
     *
     * @return \Illuminate\Auth\Access\Response
     */
    public function ban(User $user)
    {
        if($user->role->isSuperAdmin()) {
            return $this->allow();
        }

        switch (true) {

            case $user->role && $user->role->hasPermission(Permission::ALL):
                return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine if a user can unban a business.
     *
     * @param \App\User $user
     *
     * @return \Illuminate\Auth\Access\Response
     */
    public function unban(User $user)
    {
        if($user->role->isSuperAdmin()) {
            return $this->allow();
        }

        switch (true) {

            case $user->role && $user->role->hasPermission(Permission::ALL):
                return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine if a user can unban a business.
     *
     * @param \App\User $user
     *
     * @return \Illuminate\Auth\Access\Response
     */
    public function viewCheckout(?User $user, Business $business)
    {
        $provider = $business->paymentProviders()->first();

        if ($provider instanceof PaymentProvider) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Determine if a user can restrict roles.
     *
     * @param \App\User $user
     *
     * @return \Illuminate\Auth\Access\Response
     */
    public function canRestrictRoles(?User $user, Business $business)
    {
        return $this->businessUserPermissionsService->can($user, $business, 'canRestrictRoles')
            ? $this->allow()
            : $this->deny();
    }

    /**
     * Determine if a user can do refunds.
     *
     * @param \App\User $user
     *
     * @return \Illuminate\Auth\Access\Response
     */
    public function canRefundCharges(?User $user, Business $business)
    {
        return $this->businessUserPermissionsService->can($user, $business, 'canRefundCharges')
            ? $this->allow()
            : $this->deny();
    }
}
