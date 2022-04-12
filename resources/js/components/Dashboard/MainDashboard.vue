<template>
  <div class="d-flex flex-column main-dashboard">
    <template v-if="hasProviders && !hasPermission('canSeePartnerPage')">
      <!-- Today & Accept cards -->
      <div class="d-flex flex-column flex-xl-row dash-row" v-if="hasPermission('canOperateBankPayouts')">
        <div class="dashboard-card d-flex flex-column flex-grow-1">
          <div class="today-title">
            Today
          </div>

          <div class="flex-grow-1 d-flex align-items-center flex-wrap mb-0 justify-content-between">
            <div
              v-for="(item, index) in todayData"
              :key="index"
              class="today-item">
              <span class="ti-title">{{ item.title }}</span>
              <div>
                <span v-if="item.dollarSign" class="ti-sign">$</span>
                <span class="ti-text">{{ item.text }}</span>
              </div>
            </div>
          </div>
        </div>

          <div v-if="hasPermission('canOperateBankPayouts')">
            <PaymentMethodCard
              v-if="showPayNowPanel"
              title="Enable PayNow"
              text="Complete your PayNow setup to start accepting PayNow in your checkout page"
              button="Setup PayNow"
              :link="`/business/${business_id}/payment-provider?tab=paynow`"/>

            <PaymentMethodCard
              v-else-if="showStripePanel"
              title="Accept Cards"
              text="Connect your stripe account to start accepting credit and debit card in your checkout page"
              button="Setup Now"
              :link="`/business/${business_id}/payment-provider?tab=stripe`"/>

            <PaymentMethodCard
              v-else
              title="PayNow integration with Xero"
              text="Integrate HitPay with xero to start accepting paynow payments on your invoices with instant confirmation"
              button="Connect Now"
              :link="`/business/${business_id}/integration/xero/home`"/>
          </div>
      </div>

      <!-- Transactions & payouts -->
      <div class="d-flex flex-column flex-xl-row dash-row">
        <TableCard
          class="mr-xl-4 flex-grow-1"
          title="Recent Transactions"
          :link="`/business/${business_id}/charge`">
          <table
            v-if="lastTransactions.length > 0"
            class="dash-table w-100">
            <tr
              v-for="(item, index) in lastTransactions"
              :key="`${index}-${item.id}`">
              <td class="small">{{ item.closed_at | date2 }}</td>
              <td class="small">
                <span class="text-black">{{ item.customer.email || 'No customer info' }}</span><br/>
                <span class="text-weight-light">Order ID:</span> {{ item.id }}
              </td>
              <td class="text-center">
                <img height="24" :src="`/icons/payment-methods-2/${paymentMethodImages[item.payment_provider.charge.method]}`"/>
              </td>
              <td class="amount-big"><span class="amount-small">$</span>{{ item.amount.toFixed(2) }}</td>
            </tr>
          </table>
          <div v-else class="no-data h-100 p-4 d-flex align-items-center justify-content-center">
            There are no transactions yet
          </div>
        </TableCard>

        <TableCard
          v-if="hasPermission('canOperateBankPayouts')"
          class="flex-grow-1"
          title="Bank Payouts"
          :link="bankPayoutsLink"
        >
          <table
            v-if="daily_data.lastPayouts.length > 0"
            class="dash-table w-100">
            <tr
              v-for="(item, index) in daily_data.lastPayouts"
              :key="`${index}`">
              <td class="small">{{ item.created_date | date2 }}</td>
              <td>
                {{ getPayoutType(item.type) }}
              </td>
              <td class="amount-big"><span class="amount-small">$</span>{{ item.amount }}</td>
            </tr>
          </table>
          <div v-else class="no-data h-100 p-4 d-flex align-items-center justify-content-center">
            There are no payouts yet
          </div>
        </TableCard>
      </div>
    </template>

    <!-- Quick links -->
    <div class="d-flex flex-column" v-if="!hasPermission('canSeePartnerPage')">
      <span class="title-quick-links mb-3">Quick Links</span>
      <div class="d-flex justify-content-between flex-wrap">
        <div
          v-for="(ql, index) in quickLinks"
          :key="index"
          class="dashboard-ql">

          <a :href="ql.link" target="_blank" class="text-center h-100 d-block">
            <div class="h-100 d-flex flex-column align-items-center justify-content-between">
              <div class="flex-grow-1 d-flex align-items-center">
                <img :src="ql.icon" :alt="ql.text" height="90">
              </div>
              <span class="ql-name text-dark">{{ ql.text }}</span>
            </div>
          </a>
        </div>
      </div>
    </div>

    <!-- SignPass panel -->
      <VerificationWarning
          v-if="country_code == 'sg'"
          v-model="is_show_modal_verification"
          :businessId="business_id"
          :business="business"
      />
  </div>
</template>

<script>
import moment from 'moment'
import TableCard from './TableCard'
import PaymentMethodCard from './PaymentMethodCard'
import VerificationWarning from './VerificationWarning'

const lastCheckItem = 'verified_wit_my_info_sg_warning'

export default {
  name: 'MainDashboard',
  components: {
    TableCard,
    PaymentMethodCard,
    VerificationWarning
  },
  props: {
    business: Object,
    business_id: String,
    is_show_modal_verification: Boolean,
    country_code: String,
    daily_data: Object,
    user: Object
  },

    /**
    * Prepare the component (Vue 1.x).
    */
    ready() {
        this.prepareComponent();
    },

    /**
    * Prepare the component (Vue 2.x).
    */
    mounted() {
        this.prepareComponent();
    },

  data () {
    return {
        partner: this.user.business_partner,
      quickLinks: [
        {
          text: 'Add HitPay to Shopify',
          icon: '/hitpay/images/shopify.png',
          link: 'https://hitpay.zendesk.com/hc/en-us/articles/900000685746-Add-PayNow-to-your-Shopify-E-Commerce-Store'
        },
        {
          text: 'Add HitPay to WooCommerce',
          icon: '/hitpay/images/woocommerce.png',
          link: 'https://hitpay.zendesk.com/hc/en-us/articles/900000771503-Add-HitPay-to-your-WooCommerce-Store'
        },
        {
          text: 'Add HitPay to Xero',
          icon: '/hitpay/images/xero.png',
          link: 'https://hitpay.zendesk.com/hc/en-us/articles/900003418126-Setting-up-the-Xero-Integration-with-HitPay'
        },
        {
          text: 'Add HitPay to Magento',
          icon: '/hitpay/images/magento.png',
          link: 'https://hitpay.zendesk.com/hc/en-us/articles/900002303026-Add-HitPay-to-Magento-Online-Stores'
        },
        {
          text: 'Add HitPay to Prestashop',
          icon: '/hitpay/images/prestashop.png',
          link: 'https://hitpay.zendesk.com/hc/en-us/articles/900001912306-Add-HitPay-to-Prestashop-Online-Stores'
        },
        {
          text: 'Add HitPay to EasyStore',
          icon: '/hitpay/images/easystore.png',
          link: 'https://hitpay.zendesk.com/hc/en-us/articles/900004982143-HitPay-EasyStore-Payment-Gateway-Singapore-Add-HitPay-to-EasyStore-PayNow-QR-EasyStore-Singapore'
        },
        {
          text: 'Add HitPay to OpenCart',
          icon: '/hitpay/images/opencart.svg',
          link: 'https://hitpay.zendesk.com/hc/en-us/articles/900003748706-HitPay-OpenCart-Payment-Gateway-Singapore-Add-HitPay-to-OpenCart'
        }
      ],
      paymentMethodImages: {
        paynow_online: 'paynow.png',
        card: 'card.svg',
        alipay: 'alipay.png',
        wechat: 'wechat.png',
        grabpay: 'grabpay.png',
        grabpay_direct: 'grabpay.png',
        grabpay_paylater: 'grabpay.png',
        cash: 'cash.png',
        card_present: 'card_reader.svg',
        shopee_pay: 'shopee_pay.png',
        zip: 'zip.png'
      },
      lastTransactions: [],
    }
  },
  computed: {
    bankPayoutsLink() {
        if(!this.hasPermission('canOperateBankPayouts')) {
            return null;
        }

        return `/business/${this.business_id}/payment-provider/paynow/payout`;
    },
    hasProviders () {
      return this.daily_data.providers
    },
    showPayNowPanel () {
      return this.hasProviders && !this.daily_data.providers.find(p => p === 'dbs_sg')
    },
    showStripePanel () {
      return this.hasProviders && !this.daily_data.providers.find(p => p === 'stripe_sg')
    },
    todayData () {
      let businessCurrency = this.business.currency;
      return [
        {
          title: businessCurrency.toUpperCase() + ' Sales',
          dollarSign: true,
          text: this.daily_data.currencies.find(c => c.currency === businessCurrency).amount
        },
        {
          title: 'USD Sales',
          dollarSign: true,
          text: this.daily_data.currencies.find(c => c.currency === 'usd').amount
        },
        {
          title: 'Transactions',
          dollarSign: false,
          text: this.daily_data.transactionsCount
        }
      ]
    },
  },
  methods: {
      prepareComponent() {
          this.getLastTransactions();
      },
    hasPermission(permission) {
      return this.user.businessUsersList.filter((businessUser) => {
        return businessUser.business_id == this.business_id && businessUser.permissions[permission];
      }).length;
    },
    getPayoutType (type) {
      switch (type) {
        case 'stripe': {
          return 'Stripe Payout'
        }
        case 'paynow': {
          return 'PayNow Payout'
        }
        default: {
          return 'Unknown Payout'
        }
      }
    },
    getAmountClass (amount) {
      const s = String(amount)

      return {
        'amount-big': s.length <= 5,
        'amount-small': s.length > 5
      }
    },
    getLastTransactions() {
        axios.get(this.getDomain(`v1/business/${this.business_id}/charge?per_page=5`, 'api'), {
            withCredentials: true
        })
          .then(response => {
              this.lastTransactions = response.data.data;
              console.log('last transactions');
              console.log(this.lastTransactions);
          });
    }
  },
}
</script>

<style lang="scss">
.main-dashboard {
  .dash-row > * {
    margin-bottom: 24px;
  }

  .dash-row {
    .transactions-card {
      flex-grow: 1;
    }

    .payouts-card {
      flex-grow: 1;
    }
  }

  .dashboard-card  {
    border-radius: 6px;
    background-color: white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, .2);
    padding: 24px 32px;
    min-width: 300px;
    height: 211px;

    .today-title {
      font-size: 18px;
      font-weight: 500;
      color: #4A4A4A;
    }

    .today-item {
      line-height: 1.16;
      margin-right: 32px;

      .ti-sign {
        font-size: 18px;
      }

      .ti-title {
        font-size: 14px;
        color: #9B9B9B;
      }

      .ti-text {
        font-size: 32px;
        font-weight: 500;
      }
    }
  }

  .dash-table {
    tr:not(:last-child) {
      border-bottom: .5px solid #D9D9D9;
    }

    td:first-child {
      padding-left: 0;
    }

    td {
      height: 44px;
      padding: 4px 8px;
      color: #4A4A4A;

      @media screen and (max-width: 576px) {
        padding: 4px;
      }

      &.small {
        font-size: 12px;
      }

      &.amount-big {
        font-size: 18px;
        font-weight: 500;
        color: #4A4A4A;
        text-align: right;

        @media screen and (max-width: 576px) {
          font-size: 16px;
        }
      }

      .amount-small {
        font-size: 14px;
        font-weight: 500;
        color: #4A4A4A;
      }
    }
  }

  .no-data {
    color: #4A4A4A;
    font-size: 18px;
    text-transform: uppercase;
  }

  .title-quick-links {
    font-size: 24px;
    font-weight: 500;
    color: #4A4A4A;
  }

  .dashboard-ql {
    width: 143px;
    height: 154px;
    padding: 0 10px 10px 10px;
    background-color: white;
    border-radius: 6px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, .2);
    margin: 8px;

    &:hover {
      transform: translateY(-2px);
    }

    .ql-name {
      font-size: 14px;
      font-weight: 600;
      line-height: 1.16 !important;
      font-family: -apple-system, BlinkMacSystemFont, sans-serif;
    }
  }
}
</style>
