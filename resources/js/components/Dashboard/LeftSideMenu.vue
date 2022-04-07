<template>
    <nav class="left-side-menu navbar-expand-md">
        <div
            class="p-2 p-md-0 pt-md-5 top-line d-flex justify-content-between justify-content-md-center align-items-center">
      <span class="d-inline d-md-none">
        {{ business.name }}
      </span>

            <svg
                class="d-none d-md-block"
                height="36"
                viewBox="0 0 576 144">
                <use xlink:href='/images/hitpay.svg#hitpay'></use>
            </svg>

            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavDropdown">
                <i class="fas fa-bars"/>
            </button>
        </div>

        <!-- Menu -->
        <div
            id="navbarNavDropdown"
            class="collapse navbar-collapse align-items-start">

            <div class="menu-items-container flex-grow-1 pb-4">
                <component
                    v-for="(item, index) in menuData"
                    :key="index"
                    class="menu-item-main"
                    :is="item.children ? 'MenuSubMenu' : 'MenuItem'"
                    :item="item"
                    :expanded="item.id === menuExpanded"
                    @expand="onExpand(item.id)"/>
            </div>
        </div>
    </nav>
</template>

<script>
import Vue from 'vue'
import MenuItem from './MenuItem'
import MenuSubMenu from './MenuSubMenu'

Vue.component('MenuItem', MenuItem)
Vue.component('MenuSubMenu', MenuSubMenu)

const bankPayoutsPaths = [
    '/business/:business_id/payment-provider/paynow/payout',
    '/business/:business_id/payment-provider/stripe/payout?type=stripe',
    '/business/:business_id/platform/payout?type=platform'
]

export default {
    name: 'LeftSideMenu',
    props: {
        business: Object,
        user: Object
    },
    data() {
        return {
            menuData: [
                {title: 'Overview', path: '/business/:business_id', icon: 'overview.svg', visible: true},
                {
                    title: 'Point of Sale',
                    icon: 'accept_payment.svg',
                    path: '/business/:business_id/point-of-sale',
                    visible: this.isVisibleMenuItem('canOperatePointOfSale')
                },
                {
                    title: 'Payment Links',
                    icon: 'payment-link.svg',
                    path: '/business/:business_id/payment-links',
                    visible: this.isVisibleMenuItem('canOperatePaymentLinks')
                },
                {
                    title: 'Recurring Plans',
                    icon: 'accept_payment_repeat.svg',
                    path: '/business/:business_id/recurring-plan',
                    visible: this.isVisibleMenuItem('canOperateRecurringPlans')
                },
                {
                    title: 'Online Shop',
                    icon: 'online_shop.svg',
                    visible: this.isVisibleMenuItem('canOperateOnlineShop'),
                    children: [
                        {title: 'Products', path: '/business/:business_id/product', visible: this.isVisibleMenuItem('canOperateOnlineShopProducts')},
                        {title: 'Product Categories', path: '/business/:business_id/product-categories', visible: this.isVisibleMenuItem('canOperateOnlineShopProductCategories')},
                        {title: 'Orders', path: '/business/:business_id/order', visible: this.isVisibleMenuItem('canOperateOnlineShopOrders')},
                        {title: 'Discount', path: '/business/:business_id/discount', visible: this.isVisibleMenuItem('canOperateOnlineShopDiscount')},
                        {title: 'Coupons', path: '/business/:business_id/coupon', visible: this.isVisibleMenuItem('canOperateOnlineShopCoupons')},
                        {title: 'Shipping', path: '/business/:business_id/setting/shipping', visible: this.isVisibleMenuItem('canOperateOnlineShopShipping')},
                        {title: 'Store Settings', path: '/business/:business_id/setting/shop', visible: this.isVisibleMenuItem('canOperateOnlineShopStoreSettings')},
                    ]
                },
                {
                    title: 'Invoicing',
                    icon: 'invoice.svg',
                    child_title: 'Try me',
                    path: '/business/:business_id/invoice',
                    visible: this.isVisibleMenuItem('canOperateInvoicing'),
                },
                {
                    title: 'Customers',
                    icon: 'customers.svg',
                    path: '/business/:business_id/customer',
                    visible: this.isVisibleMenuItem('canOperateCustomers'),
                },
                {
                    title: 'Partner',
                    icon: 'transactions.svg',
                    path: '/partner',
                    visible: this.isVisibleMenuItem('canSeePartnerPage'),
                },
                {
                    title: 'Sales & Reports',
                    icon: 'transactions.svg',
                    visible: this.isVisibleMenuItem('canOperateSalesAndReports'),
                    children: [
                        { title: 'Charges', path: '/business/:business_id/charge', visible: this.isVisibleMenuItem('canOperateCharges') },
                        { title: 'HitPay Balance', path: '/business/:business_id/balance', visible: this.isVisibleMenuItem('canManageWallets') },
                        { title: 'Bank Payouts', path: '/business/:business_id/payment-provider/paynow/payout',                     additionalPaths: bankPayoutsPaths, visible: this.isVisibleMenuItem('canOperateBankPayouts') },
                        { title: 'Fee Invoices', path: '/business/:business_id/fee-invoices', visible: this.isVisibleMenuItem('canOperateFeeInvoices') },
                    ],
                },
                {
                    title: 'Payment Gateway',
                    icon: 'integrations.svg',
                    children: [
                        {title: 'Platform', path: '/business/:business_id/platform', visible: this.isVisibleMenuItem('canOperatePaymentGatewayPlatform')},
                        {title: 'API Keys', path: '/business/:business_id/apikey', visible: this.isVisibleMenuItem('canOperatePaymentGatewayAPIKeys')},
                        {title: 'Client Keys', path: '/business/:business_id/client-key', visible: this.isVisibleMenuItem('canOperatePaymentGatewayClientKeys')},
                        {title: 'Integrations', path: '/business/:business_id/gateway-provider', visible: this.isVisibleMenuItem('canOperatePaymentGatewayIntegrations')},
                        {title: 'Checkout Customisation', path: '/business/:business_id/customisation', visible: this.isVisibleMenuItem('canOperatePaymentGatewayCheckoutCustomisation')},
                        {title: 'Cashback', path: '/business/:business_id/cashback', visible: this.isVisibleMenuItem('canOperatePaymentGatewayCashback') },
                    ],
                    visible: this.isVisibleMenuItem('canOperatePaymentGateway'),
                },
                {
                    title: 'Settings',
                    icon: 'settings.svg',
                    children: [
                        {title: 'Payment Methods', path: '/business/:business_id/payment-provider', visible: this.isVisibleMenuItem('canOperateSettingsPaymentMethods')},
                        {title: 'Bank Accounts', path: '/business/:business_id/settings/bank-accounts', visible: this.isVisibleMenuItem('canManageWallets')},
                        {title: 'Account Verification', path: '/business/:business_id/verification', visible: this.isVisibleMenuItem('canOperateSettingsAccountVerification')},
                        {title: 'Xero Integration', path: '/business/:business_id/integration/xero/home', visible: this.isVisibleMenuItem('canOperateSettingsXeroIntegration')},
                        {title: 'Quickbooks Integration', path: '/business/:business_id/integration/quickbooks/home', visible: this.isVisibleMenuItem('canOperateSettingsXeroIntegration')},
                        {
                            title: 'User Management',
                            path: '/business/:business_id/user-management',
                            visible: this.isVisibleMenuItem('canManageUsers'),
                        },
                        { title: 'Tax Settings', path: '/business/:business_id/tax-setting',visible: this.isVisibleMenuItem('canOperateTaxSettings') },
                        {title: 'Business', path: '/business/:business_id/basic-details', visible: this.isVisibleMenuItem('canUpdateBusiness')},
                        {title: 'Notifications', path: '/business/:business_id/notifications', visible: this.isVisibleMenuItem('canOperateNotifications')},
                        {title: 'Partners', path: '/business/:business_id/partners', visible: this.isVisibleMenuItem('canSeeSettingsPartners')},
                    ],
                    visible: this.isVisibleMenuItem('canOperateSettings'),

                },
                {
                    title: 'Refer and Earn',
                    icon: 'refer_earn.svg',
                    path: '/business/:business_id/referral-program',
                    visible: this.isVisibleMenuItem('canOperateReferralProgram'),
                }
            ],
            menuExpanded: -1
        }
    },
    methods: {
        onExpand(id) {
            this.menuExpanded = this.menuExpanded >= 0
                ? this.menuExpanded === id
                    ? -1
                    : id
                : id

            let item = document.getElementById(id);
            if(this.menuExpanded != -1) {
                item.classList.add('hidden-try-me');
            }else{
                item.classList.remove('hidden-try-me');
            }
        },
        processMenuItem(item, rootParent, id) {
            let newId = id
            item.id = newId++

            if (item.path) {
                item.path = item.path.replace(':business_id', this.business.id)

                if (item.additionalPaths) {
                    item.additionalPaths = item.additionalPaths.map(p => p.replace(':business_id', this.business.id))
                }

                if (window.location.href.endsWith(item.path)) {
                    this.menuExpanded = rootParent.id
                }
            }

            if (item.children) {
                item.children.forEach(child => {
                    newId = this.processMenuItem(child, rootParent, newId)
                })
            }

            return newId
        },
        getPayoutPath() {
            return this.business.payment_providers
                ? this.business.payment_providers.some(pp => pp.payment_provider === 'dbs_sg')
                    ? 0 // Paynow
                    : this.business.payment_providers.some(pp => pp.payment_provider === 'stripe_sg')
                        ? 1 // Stripe
                        : 2 // Platform
                : 2
        },
        isVisibleMenuItem(permission) {
            if (permission == 'canOperatePaymentGatewayCashback') {
                if (this.business.country !== 'sg') {
                    return false;
                } else {
                    return this.user.businessUsersList.filter((businessUser) => {
                        return businessUser.business_id == this.business.id && businessUser.permissions[permission];
                    }).length;
                }
            } else {
                return this.user.businessUsersList.filter((businessUser) => {
                    return businessUser.business_id == this.business.id && businessUser.permissions[permission];
                }).length;
            }
        },
        filterMenu(menu) {
            return menu.filter(item => {
                let itemCond = item.visible;

                if (item.children && itemCond) {
                    item.children = this.filterMenu(item.children)
                    itemCond &= item.children.length > 0
                }

                return itemCond
            })
        }
    },
    created() {
        this.menuData = this.filterMenu(this.menuData)

        // set ids
        let id = 1

        this.menuData.forEach(item => {
            id = this.processMenuItem(item, item, id)
        })
    }
}
</script>

<style lang="scss">
@import "../../../sass/padded-vscroll-mixin.scss";

$backColor: #011B5F;

.left-side-menu {
    width: 300px;
    background-color: $backColor;
    color: white;

    @media screen and (max-width: 768px) {
        top: 0;
        width: 100vw;
        min-height: 46px;
        max-height: 100vh;
        z-index: 10;
    }

    @media screen and (min-width: 768px) {
        min-height: 100vh;
        left: 0;
        height: 100vh;
        position: fixed;
    }

    .navbar-toggler {
        color: white;
    }

    #navbarNavDropdown {
        height: calc(100% - 46px);
        overflow-y: auto;
        @include padded-vscroll-bar(16px, $backColor);

        .menu-items-container {
            padding: 0px 12px 0px 17px;
            @media screen and (max-width: 768px) {
                height: 100%;
            }

            @media screen and (min-width: 768px) {
                margin-top: 45px;
            }

            // .menu-item-main {
            //     &:not(:last-child) {
            //         margin-bottom: 16px;
            //     }

            //     :not(:last-child) {
            //         margin-bottom: 16px;
            //     }
            // }
        }
    }
}
</style>
