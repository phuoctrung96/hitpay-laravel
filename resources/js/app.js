require('./bootstrap');

window.Vue = require('vue');

import VueScrollTo from 'vue-scrollto';
import Clipboard from 'v-clipboard'
import Datepicker from 'vuejs-datepicker';
import JwPagination from 'jw-vue-pagination';
import registerFilters from "./filters";
import posthog from 'posthog-js';
import { BPagination } from 'bootstrap-vue'
import Vue from 'vue';

Vue.use(VueScrollTo);
Vue.use(Clipboard);
Vue.use(Datepicker);
Vue.mixin({
    methods: {
        $ready(fn) {
            setTimeout(() => {
              this.$nextTick(fn);
            });
        },

        getAppName: () => {
            return HitPay.app_name;
        },

        getDomain: (path = null, subdomain = null) => {

            let domain = HitPay.scheme + '://';

            if (window.location.hostname === HitPay.shop_domain){
                domain = domain + HitPay.shop_domain;
            }
            else {
                if (subdomain === null) {
                    domain = domain + HitPay.domain;
                } else {
                    domain = domain + HitPay.subdomains[subdomain];
                }
            }

            if (path !== null) {
                return domain + '/' + path;
            }

            return domain;
        },

        getPusher: (name) => {
            return HitPay.pusher[name];
        },

        getPaymentGatewayValue: (name) => {
            return HitPay.payment_gateway[name];
        },

        redirect: (destination, queries = null) => {
            if (queries !== null) {
                destination += destination.indexOf('?') > -1 ? '&' : '?';
                destination += queries;
            }

            window.location.href = destination;
        },

        // TODO - 2019-09-21 - Bug
        // The scrolling is not working correctly when the user press enter in any input element. It scroll a little
        // bit, but doesn't bring scroll to the expected location.
        scrollTo: (element, offset = 24, top_padding = 72) => {
            VueScrollTo.scrollTo(element, 500, {
                offset: -(top_padding + offset),
            });
        },

        validateDate: (value) => {
            return /^[\d]{4}-[\d]{2}-[\d]{2}$/.test(value);
        },

        // TODO - 2019-09-21
        // The email validation REGEX rule is copied from https://stackoverflow.com/questions/46155/how-to-validate-an-email-address-in-javascript
        // and was not really tested. Anyway, it is working fine so far.
        validateEmail: (value) => {
            return /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(value);
        },

        // TODO - 2019-09-21
        // The email validation REGEX rule is copied from https://stackoverflow.com/questions/46155/how-to-validate-an-email-address-in-javascript
        // and was not really tested. Anyway, it is working fine so far.
        validatePhoneNumber: (value, country = 'sg') => {
            if (country === 'sg') {
                return /^(\+|)(65[3689]\d{7})$/.test(value);
            } else if (country === 'my') {
                return /^(\+|)(601([2-4]|[6-9])\d{7})|(\+6011\d{8})|(\+60([3-9])\d{7,9})$/.test(value);
            }

            return false;
        },

        postHogCaptureData: (event, businessId, email, data) => {
          if (HitPay.posthog && HitPay.posthog.api_key) {
            posthog.init(HitPay.posthog.api_key, {
              api_host: HitPay.posthog.api_host,
                loaded:
                    function (posthog) {
                        posthog.identify(businessId);

                        if(email !== '')
                            posthog.people.set({ email: email });
                    }
            });

            if(event !== '')
                posthog.capture(event, data);
          }
        },

        postHogOnlyCaptureData: (event, data) => {
            if(event !== '')
                posthog.capture(event, data);
        }
    },
});

registerFilters(Vue);

Vue.component('jw-pagination', JwPagination);
Vue.component('b-pagination', BPagination)

Vue.component('admin-business-charge-export', require('./components/Admin/ExportCharge').default);
Vue.component('admin-business-commission-export', require('./components/Admin/ExportCommission').default);
Vue.component('admin-partners-export', require('./components/Admin/ExportPartners').default);
Vue.component('admin-business-fast-payout-export', require('./components/Admin/ExportFastPayout').default);
Vue.component('admin-business-refund-export', require('./components/Admin/ExportRefund').default);
Vue.component('admin-business-referral-fees-export', require('./components/Admin/ExportBusinessReferralFees').default);
Vue.component('admin-campaign-create-edit', require('./components/Admin/Campaign').default);
Vue.component('admin-onboarding-index', require('./components/Admin/OnboardingIndex').default);
Vue.component('admin-onboarding-provider', require('./components/Admin/OnboardingProvider').default);
Vue.component('authentication-secret-disabling', require('./components/Authentication/Secret/DisablingComponent').default);
Vue.component('authentication-secret-enabling', require('./components/Authentication/Secret/EnablingComponent').default);
Vue.component('authentication-checkpoint', require('./components/Authentication/CheckpointComponent').default);
Vue.component('authentication-login', require('./components/Authentication/LoginComponent').default);
Vue.component('authentication-password-email', require('./components/Authentication/Password/EmailComponent').default);
Vue.component('authentication-password-reset', require('./components/Authentication/Password/ResetComponent').default);
Vue.component('authentication-password-update', require('./components/Authentication/Password/UpdateComponent').default);
Vue.component('authentication-register', require('./components/Authentication/RegisterComponent').default);
Vue.component('authentication-register-complete', require('./components/Authentication/RegisterCompleteComponent').default);
Vue.component('authentication-register-partner', require('./components/Authentication/RegisterPartnerComponent').default);
Vue.component('business-basic-detail', require('./components/Business/BasicDetail').default);
Vue.component('business-charge', require('./components/Business/Charge').default);
Vue.component('business-paynow-refund', require('./components/Business/PayNowRefund').default);
Vue.component('business-refund', require('./components/Business/Refund').default);
Vue.component('business-charge-export', require('./components/Business/ExportCharge').default);
Vue.component('business-product-export', require('./components/Business/ExportProduct').default);
Vue.component('business-platform-charge-export', require('./components/Business/ExportPlatformCharge').default);
Vue.component('business-create', require('./components/Business/CreateComponent').default);
Vue.component('business-customer', require('./components/Business/Customer').default);
Vue.component('business-customer-export', require('./components/Business/ExportCustomer').default);
Vue.component('business-customer-bulk', require('./components/Business/BulkCustomers').default);
Vue.component('business-order-export', require('./components/Business/ExportOrder').default);
Vue.component('business-delivery-export', require('./components/Business/ExportDeliveryOrder').default);
Vue.component('business-order-list', require('./components/Business/OrderList').default);
Vue.component('business-point-of-sale', require('./components/Business/PointOfSale').default);
Vue.component('business-product', require('./components/Business/Product').default);
Vue.component('business-discount-create-edit', require('./components/Business/Discount').default);
Vue.component('business-coupon-create-edit', require('./components/Business/Coupon').default);
Vue.component('business-cashback-create-edit', require('./components/Business/Cashback').default);
Vue.component('business-product-bulk', require('./components/Business/BulkProducts').default);
Vue.component('business-product-edit', require('./components/Business/ProductEdit').default);
Vue.component('business-product-list', require('./components/Business/ProductList').default);
Vue.component('business-shipping', require('./components/Business/Shipping').default);
Vue.component('business-shipping-list', require('./components/Business/ShippingList').default);
Vue.component('business-commission-export', require('./components/Business/ExportCommission').default);
Vue.component('business-transfer-export', require('./components/Business/ExportTransfer').default);
Vue.component('business-payout-breakdown-export', require('./components/Business/ExportPayoutBreakdown').default);
Vue.component('business-recurring-plan', require('./components/Business/RecurringPlan').default);
Vue.component('business-recurring-plan-send', require('./components/Business/RecurringPlanSendEmail').default);
Vue.component('business-recurring-plan-template', require('./components/Business/RecurringPlanTemplate').default);
Vue.component('business-invoice', require('./components/Business/Invoice').default);
Vue.component('business-invoice-detail', require('./components/Business/InvoiceDetail').default);
Vue.component('business-invoice-bulk', require('./components/Business/BulkInvoices').default);
Vue.component('business-notifications', require('./components/Business/Notifications').default);
Vue.component('business-edit-slug', require('./components/Business/EditSlug').default);
Vue.component('business-hotglue', require('./components/Business/Hotglue').default);
Vue.component('product-category-create-edit', require('./components/Business/ProductCategory').default);
Vue.component('add-product', require('./components/Shop/AddProduct').default);
Vue.component('create-payment-link', require('./components/Business/PaymentLinkCreate').default);
Vue.component('product-featured', require('./components/Shop/ProductFeatured').default);
Vue.component('product-images', require('./components/Shop/ProductImages').default);
Vue.component('product-images-mobile', require('./components/Shop/ProductImagesMobile').default);
Vue.component('cart-items', require('./components/Shop/CartItems').default);
Vue.component('pre-checkout', require('./components/Shop/PreCheckout').default);
Vue.component('checkout', require('./components/Shop/Checkout').default);
Vue.component('product-list', require('./components/Shop/ProductList').default);
Vue.component('shop-checkout-request-form', require('./components/Shop/CheckoutPaymentRequestForm').default);
Vue.component('recurring-billing-checkout', require('./components/RecurringBiliingCheckout').default);
Vue.component('user', require('./components/UserComponent').default);
Vue.component('xero-account-settings', require('./components/Business/XeroSettings').default);
Vue.component('user-setup', require('./components/UserSetupComponent').default);
Vue.component('product-url-settings', require('./components/Business/ProductURLSetting').default);
Vue.component('subscription', require('./components/Subscription').default);
Vue.component('shop-settings', require('./components/Business/ShopState').default);
Vue.component('tax-invoices', require('./components/Business/TaxInvoice').default);
Vue.component('partners', require('./components/Business/Partners').default);
Vue.component('verification', require('./components/Business/Verification').default);
Vue.component('manual-verification', require('./components/Business/Verification/Manual').default);

Vue.component('passport-clients', require('./components/Passport/Clients.vue').default);
Vue.component('passport-authorized-clients', require('./components/Passport/AuthorizedClients.vue').default);
Vue.component('passport-personal-access-tokens', require('./components/Passport/PersonalAccessTokens.vue').default);

Vue.component('business-checkout-customisation', require('./components/Admin/CheckoutCustomisation/CheckoutCustomisation.vue').default);
Vue.component('left-side-menu', require('./components/Dashboard/LeftSideMenu.vue').default);
Vue.component('main-layout', require('./components/Dashboard/MainLayout.vue').default);
Vue.component('login-register-layout', require('./components/Authentication/LoginRegisterLayout.vue').default);
Vue.component('onboard-layout', require('./components/Authentication/OnboardLayout.vue').default);
Vue.component('payment-methods', require('./components/Business/PaymentMethods/PaymentMethods.vue').default);
Vue.component('main-dashboard', require('./components/Dashboard/MainDashboard.vue').default);
Vue.component('business-users', require('./components/Business/Users/BusinessUsers.vue').default);
Vue.component('balance-withdrawal', require('./components/Business/Wallets/Withdrawal.vue').default);
Vue.component('balance-top-up', require('./components/Business/Wallets/TopUp').default);
Vue.component('main-dashboard', require('./components/Dashboard/MainDashboard.vue').default);
Vue.component('business-users', require('./components/Business/Users/BusinessUsers.vue').default);
Vue.component('business-role-restrictions', require('./components/Business/RoleRestrictions.vue').default);
Vue.component('getting-started', require('./components/Dashboard/GettingStarted.vue').default);

Vue.component('paynow-settings', require('./components/Business/PaymentMethods/PayNowSettings.vue').default);
Vue.component('stripe-settings', require('./components/Business/PaymentMethods/StripeSettings.vue').default);
Vue.component('shopee-settings', require('./components/Business/PaymentMethods/ShopeeSettings.vue').default);
Vue.component('hoolah-settings', require('./components/Business/PaymentMethods/HoolahSettings.vue').default);
Vue.component('grabpay-settings', require('./components/Business/PaymentMethods/GrabPaySettings.vue').default);
Vue.component('zip-settings', require('./components/Business/PaymentMethods/ZipSettings.vue').default);
Vue.component('charge-wait', require('./components/Shop/ChargeWait.vue').default);
Vue.component('business-help-guide', require('./components/Business/HelpGuide').default);

Vue.component('business-rates', require('./components/Admin/Rates/BusinessRates').default);

Vue.component('business-settings-bank-accounts-create', require('./components/Business/BankAccounts/Create').default);
Vue.component('business-settings-bank-accounts-edit', require('./components/Business/BankAccounts/Edit').default);

Vue.component('provider-logo', require('./components/UI/ProviderLogo').default);

Vue.component('business-onboard-paynow-create',
    require('./components/Business/Onboard/Paynow/Create').default);

Vue.component('stripe-onboard-verification-company',
    require('./components/Business/PaymentMethods/Stripe/Onboard/Company.vue').default);
Vue.component('stripe-onboard-verification-individual',
    require('./components/Business/PaymentMethods/Stripe/Onboard/Individual.vue').default);

Vue.component('verification-cognito-show',
    require('./components/Business/Verification/Cognito/Show.vue').default);
/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const app = new Vue({
    el: '#app',
});
