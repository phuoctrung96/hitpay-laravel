<?php


namespace App\Services;


use App\Business;
use App\Business\BusinessUser;
use App\Enumerations\BusinessRole;
use App\Enumerations\Restriction;
use App\Models\BusinessPartner;
use App\Role;
use App\User;
use Illuminate\Database\Eloquent\Model;

class BusinessUserPermissionsService
{
    private static $permissions = [
        'canOperatePointOfSale',
        'canOperateRecurringPlans',
        'canOperateOnlineShop',
        'canOperateOnlineShopDashboard',
        'canOperateOnlineShopInsight',
        'canOperateOnlineShopProducts',
        'canOperateOnlineShopProductCategories',
        'canOperateOnlineShopOrders',
        'canOperateOnlineShopDiscount',
        'canOperateOnlineShopCoupons',
        'canOperateOnlineShopShipping',
        'canOperateOnlineShopStoreSettings',
        'canOperateOnlineShopHotglueIntegration',
        'canOperateOnlineShopDisableEnableStore',
        'canOperateInvoicing',
        'canOperatePaymentLinks',
        'canOperateCustomers',
        'canOperateCharges',
        'canOperateFeeInvoices',
        'canOperateSalesAndReports',
        'canOperateBankPayouts',
        'canOperatePaymentGateway',
        'canOperatePaymentGatewayPlatform',
        'canOperatePaymentGatewayAPIKeys',
        'canOperatePaymentGatewayClientKeys',
        'canOperatePaymentGatewayIntegrations',
        'canOperatePaymentGatewayCheckoutCustomisation',
        'canOperatePaymentGatewayCashback',
        'canOperateSettings',
        'canOperateSettingsPaymentMethods',
        'canOperateSettingsAccountVerification',
        'canOperateSettingsXeroIntegration',
        'canOperateTaxSettings',
        'canManageUsers',
        'canRestrictRoles',
        'canSendBalanceToBank',
        'canManageWallets',
        'canUpdateBusiness',
        'canManageBusiness',
        'canDeactivateBusiness',
        'canReactivateBusiness',
        'canDeleteAdminUsers',
        'canChangeBankAccount',
        'canExportCharges',
        'canExportProducts',
        'canRemoveStripeAccount',
        'canOperateNotifications',
        'canSeePartnerPage',
        'canSeeSettingsPartners',
        'canOperateReferralProgram',
        'canOperatePaymentGatewayShopifyPaymentApp',
        'canRefundCharges',
        'canManageCustomer',
    ];

    public function get(BusinessUser $businessUser): array
    {
        $permissions = [];
        foreach (self::$permissions as $permission) {
            $permissions[$permission] = $this->$permission($businessUser);
        }
        return $permissions;
    }

    public function can(User $executor, Business $business, string $permission): bool
    {
        if (!in_array($permission, self::$permissions)) {
            throw new \InvalidArgumentException('Permission "' . $permission . '" does not exist');
        }

        if($executor->role->isSuperAdmin()) {
            return true;
        }

        if (!$businessUser = $this->getBusinessUser($executor, $business)) {
            return false;
        }

        return $this->{$permission}($businessUser);
    }

    public function getBusinessUser(User $user, Business $business): ?BusinessUser
    {
        if($user->role->isSuperAdmin()) {
            $ownerRole = Role::owner();

            $businessUser = new BusinessUser();
            $businessUser->user_id = $user->id;
            $businessUser->user = $user;
            $businessUser->role_id = $ownerRole->id;
            $businessUser->role = $ownerRole;
            $businessUser->invite_accepted_at = now();

            return $businessUser;
        }

        return $business->businessUsers()->where('user_id', $user->id)->first();
    }

    private function canRemoveStripeAccount(BusinessUser $businessUser): bool
    {
        return $businessUser->isOwner() || $businessUser->isAdmin();
    }

    private function canUpdateBusiness(BusinessUser $businessUser): bool
    {
        return $businessUser->isOwner() || $businessUser->isAdmin();
    }

    private function canManageBusiness(BusinessUser $businessUser): bool
    {
        return $businessUser->isOwner() || $businessUser->isAdmin();
    }

    private function canOperateNotifications(BusinessUser  $businessUser): bool
    {
        if(auth()->user()->businessPartner instanceof BusinessPartner) {
            return false;
        }

        return $businessUser->isOwner() || $businessUser->isAdmin();
    }

    private function canDeactivateBusiness(BusinessUser $businessUser): bool
    {
        return $businessUser->isOwner();
    }

    private function canReactivateBusiness(BusinessUser $businessUser): bool
    {
        return $businessUser->isOwner();
    }

    private function canOperatePointOfSale(BusinessUser $businessUser): bool
    {
        if(auth()->user()->businessPartner instanceof BusinessPartner) {
            return false;
        }

        return true;
    }

    private function canSendBalanceToBank(BusinessUser $businessUser): bool
    {
        return $businessUser->isOwner() || $businessUser->isAdmin();
    }

    private function canManageWallets(BusinessUser $businessUser): bool
    {
        return $businessUser->isOwner() || $businessUser->isAdmin();
    }

    private function canOperateRecurringPlans(BusinessUser $businessUser): bool
    {
        if(auth()->user()->businessPartner instanceof BusinessPartner) {
            return false;
        }

        return true;
    }

    private function canOperatePaymentLinks(BusinessUser $businessUser): bool
    {
        if(auth()->user()->businessPartner instanceof BusinessPartner) {
            return false;
        }

        return true;
    }

    private function canOperateOnlineShop(BusinessUser $businessUser): bool
    {
        if(auth()->user()->businessPartner instanceof BusinessPartner) {
            return false;
        }

        return !$businessUser->isCashier();
    }

    private function canOperateOnlineShopDashboard(BusinessUser $businessUser): bool
    {
        if(auth()->user()->businessPartner instanceof BusinessPartner) {
            return false;
        }

        if(!$businessUser->business){
            return false;
        }

        $isHasOrder = ($businessUser->business->orders->count()> 0)? true: false;
        return !$businessUser->isCashier() && !$isHasOrder;
    }

    private function canOperateOnlineShopInsight(BusinessUser $businessUser): bool
    {
        if(auth()->user()->businessPartner instanceof BusinessPartner) {
            return false;
        }

        if(!$businessUser->business){
            return false;
        }

        $isHasOrder = ($businessUser->business->orders->count()> 0)? true: false;
        return !$businessUser->isCashier() && $isHasOrder;
    }

    private function canOperateOnlineShopProducts(BusinessUser $businessUser): bool
    {
        return !$businessUser->isCashier();
    }

    private function canOperateOnlineShopProductCategories(BusinessUser $businessUser): bool
    {
        return !$businessUser->isCashier();
    }

    private function canOperateOnlineShopOrders(BusinessUser $businessUser): bool
    {
        return !$businessUser->isCashier();
    }

    private function canOperateOnlineShopDiscount(BusinessUser $businessUser): bool
    {
        return !$businessUser->isCashier();
    }

    private function canOperateOnlineShopCoupons(BusinessUser $businessUser): bool
    {
        return !$businessUser->isCashier();
    }

    private function canOperateOnlineShopShipping(BusinessUser $businessUser): bool
    {
        return !$businessUser->isCashier();
    }

    private function canOperateOnlineShopStoreSettings(BusinessUser $businessUser): bool
    {
        return !$businessUser->isCashier();
    }

    private function canOperateOnlineShopHotglueIntegration(BusinessUser  $businessUser): bool
    {
        return $businessUser->isOwner() || $businessUser->isAdmin() || $businessUser->isManager();
    }

    private function canOperateOnlineShopDisableEnableStore(BusinessUser $businessUser): bool
    {
        return !$businessUser->isCashier();
    }

    private function canOperateInvoicing(BusinessUser $businessUser): bool
    {
        if(auth()->user()->businessPartner instanceof BusinessPartner) {
            return false;
        }

        return true;
    }

    private function canOperateCustomers(BusinessUser $businessUser): bool
    {
        if(auth()->user()->businessPartner instanceof BusinessPartner) {
            return false;
        }

        return !$businessUser->isCashier();
    }

    private function canOperateCharges(BusinessUser $businessUser): bool
    {
        if(auth()->user()->businessPartner instanceof BusinessPartner) {
            return false;
        }

        return true;
    }

    private function canOperateTaxSettings(BusinessUser $businessUser): bool
    {
        if(auth()->user()->businessPartner instanceof BusinessPartner) {
            return false;
        }

        return !$businessUser->isCashier();
    }

    private function canOperateFeeInvoices(BusinessUser $businessUser): bool
    {
        return true;
    }

    private function canOperateSalesAndReports(BusinessUser $businessUser): bool
    {
        return true;
    }

    private function canOperateBankPayouts(BusinessUser $businessUser): bool
    {
        return !$businessUser->isCashier();
    }

    private function canOperatePaymentGateway(BusinessUser $businessUser): bool
    {
        if(auth()->user()->businessPartner instanceof BusinessPartner) {
            return false;
        }

        return !$businessUser->isCashier();
    }

    private function canOperatePaymentGatewayPlatform(BusinessUser $businessUser): bool
    {
        return $businessUser->business ? $businessUser->business->platform_enabled : false;
    }

    private function canOperatePaymentGatewayAPIKeys(BusinessUser $businessUser): bool
    {
        return !$businessUser->isCashier();
    }

    private function canOperatePaymentGatewayClientKeys(BusinessUser $businessUser): bool
    {
        return !$businessUser->isCashier();
    }

    private function canOperatePaymentGatewayIntegrations(BusinessUser $businessUser): bool
    {
        return !$businessUser->isCashier();
    }

    private function canOperatePaymentGatewayCheckoutCustomisation(BusinessUser $businessUser): bool
    {
        return !$businessUser->isCashier();
    }

    private function canOperatePaymentGatewayCashback(BusinessUser $businessUser): bool
    {
        return !$businessUser->isCashier();
    }

    private function canOperateSettings(BusinessUser $businessUser): bool
    {
        return !$businessUser->isCashier();
    }

    private function canOperateSettingsPaymentMethods(BusinessUser $businessUser): bool
    {
        return !$businessUser->isCashier();
    }

    private function canOperateSettingsAccountVerification(BusinessUser $businessUser): bool
    {
        return !$businessUser->isCashier();
    }

    private function canOperateSettingsXeroIntegration(BusinessUser $businessUser): bool
    {
        if(auth()->user()->businessPartner instanceof BusinessPartner) {
            return false;
        }

        return !$businessUser->isCashier();
    }

    private function canManageUsers(BusinessUser $businessUser): bool
    {
        if(auth()->user()->businessPartner instanceof BusinessPartner) {
            return false;
        }

        return $businessUser->isOwner() || $businessUser->isAdmin();
    }

    private function canRestrictRoles(BusinessUser $businessUser): bool
    {
        if(auth()->user()->businessPartner instanceof BusinessPartner) {
            return false;
        }

        return $businessUser->isOwner() || $businessUser->isAdmin();
    }



    private function canSeeSettingsPartners(BusinessUser $businessUser): bool
    {
        if(auth()->user()->businessPartner instanceof BusinessPartner) {
            return false;
        }

        return true;
    }

    private function canDeleteAdminUsers(BusinessUser $businessUser)
    {
        return $businessUser->isOwner();
    }

    private function canChangeBankAccount(BusinessUser $businessUser)
    {
        return $businessUser->isOwner() || $businessUser->isAdmin();
    }

    private function canExportCharges(BusinessUser $businessUser)
    {
        return !$businessUser->isCashier();
    }

    private function canExportProducts(BusinessUser $businessUser)
    {
        return !$businessUser->isCashier();
    }

    private function canSeePartnerPage()
    {
        return auth()->user()->businessPartner instanceof BusinessPartner;
    }

    private function canOperateReferralProgram()
    {
        return !auth()->user()->businessPartner instanceof BusinessPartner;
    }

    private function canOperatePaymentGatewayShopifyPaymentApp(BusinessUser $businessUser): bool
    {
        return !$businessUser->isCashier();
    }

    private function canRefundCharges(BusinessUser $businessUser): bool
    {
        if ($businessUser->isCashier() && $businessUser->role->hasRestriction(Restriction::REFUND, $businessUser->business))
            return false;

        return true;
    }

    /**
     * @param BusinessUser $businessUser
     * @return bool
     */
    private function canManageCustomer(BusinessUser $businessUser): bool
    {
        if ($businessUser->isOwner() || $businessUser->isAdmin() || $businessUser->isManager()) {
            return true;
        }

        return false;
    }
}
