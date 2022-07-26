<template>
  <div class="payment-methods">
    <!-- methods list -->
    <div
      v-for="method in availableProviders"
      :key="method.slug"
      class="card d-flex flex-xs-column flex-sm-row align-items-center mt-3 px-3 py-2">

      <div
        v-if="method.color"
        :style="{ 'background-color': method.color }"
        class="icon-container d-flex align-items-center justify-content-center my-1 flex-shrink-0">

        <img
          :src="method.logo"
          :height="method.height || 20"
          :width="method.width"
        />
      </div>

      <img
        v-else
        class="mt-1"
        :src="method.logo"
        width="72"
        height="72"
        style="margin-left: -4px"/>

      <div class="d-flex flex-column align-items-center align-items-sm-start justify-content-center mx-2 flex-grow-1">
        <span class="caption">{{ method.title }}</span>

        <div
          v-if="method.availableMethods && method.availableMethods.length > 0"
          class="d-flex flex-column flex-sm-row available-methods flex-sm-wrap align-items-center align-items-sm-start">
          <span class="flex-shrink-0">Available methods:</span>

          <div class="d-flex flex-wrap justify-content-center justify-content-sm-start">
            <div
              v-for="(img, index) in method.availableMethods"
              :key="index"
              class="ml-1 d-flex justify-content-center justtify-content-sm-start method"
              :class="{ full: images[img].fullSize, 'full-border': images[img].fullBorder }">

              <img :src="images[img].img"/>
            </div>
          </div>
        </div>
        -->
      </div>

      <!-- Action -->
      <div
        v-if="requirePaynow(method)"
        class="require-paynow">
        Please connect PayNow first
      </div>

      <div
        v-else-if="method.requireBusinessVerification && !business_verified && !paymentMethodConnected(method.slug)"
        class="d-flex flex-column align-items-center not-available text-right">
        <template v-if="business.business_type === 'individual'">{{ method.title }} is not available for individual sellers. </template>Please check your account verification.
      </div>

      <div
        v-else
        class="d-flex flex-column align-items-center">
        <a
          class="view-details d-flex align-items-center justify-content-center"
          :class="{ 'view-details': paymentMethodConnected(method.slug), 'connect': !paymentMethodConnected(method.slug) }"
          :href="`/business/${business.id}/payment-provider/${method.link}`">
          {{ paymentMethodConnected(method.slug) ? 'VIEW DETAILS' : 'Connect' }}
        </a>

        <span
          v-if="status(method.slug)">
          <span v-html="status(method.slug)"></span>
        </span>
      </div>
    </div>
  </div>
</template>

<script>
  import { merge } from 'lodash'

  export default {
    name: 'PaymentMethods',
    props: {
      banks_list: Array,
      providers: Array,
      disabled_providers: Array,
      current_business_user: Object,
      business_verified: Boolean
    },
    data () {
      return {
        business: window.Business,
        user: window.User,
        providersData: Array,
        page: '',
        message: '',
        messageClass: '',
        supportedPaymentProviders: [],
        images: {
          paynow: { img: '/icons/payment-providers/paynow.png' },
          visa: { img: '/icons/payment-brands/visa.png' },
          master: { img: '/icons/payment-brands/master.png' },
          amex: { img: '/icons/payment-brands/amex.svg', fullSize: true },
          wechat: { img: '/icons/payment-methods/weechat-new2.svg' },
          alipay: { img: '/icons/payment-methods/alipay-new.svg' },
          apple: { img: '/icons/payment-brands/apple-pay.svg', fullSize: true },
          grabpay: { img: '/icons/payment-methods/grabpay2.png' },
          grab_paylater: { img: '/icons/payment-brands/grabpay_paylater.svg', fullSize: true },
          unionpay: { img: '/icons/payment-brands/unionpay.svg', fullSize: true },
          googlepay: { img: '/icons/payment-brands/gpay.svg', fullBorder: true },
          zip: { img: '/icons/payment-brands/zip.svg', fullBorder: true },
          shopee: { img: '/icons/payment-brands/shopee.svg', fullBorder: true },
          fpx: { img: '/icons/payment-brands/fpx.png' }
        }
      }
    },
    created () {
      this.providersData = this.providers
    },
    computed: {
      availableProviders () {
        return this.supportedPaymentProviders.filter(p => !this.disabled_providers.includes(p.slug))
      },
      paynow () {
        const provider = this.providersData.find(p => p.payment_provider === 'dbs_sg')

        if (provider) {
          // const code = provider.payment_provider_account_id.split('@')

          return {
            company_uen: provider.data.company.uen,
            company_name: provider.data.company.name,
            // bank_account_name: provider.data.account.name,
            // bank_name: this.banks_list[code[0]],
            // bank_swift_code: code[0],
            // bank_account_no: code[1],
          }
        } else {
          return undefined
        }
      },
      stripe () {
        return this.providersData.find(p => p.payment_provider === 'stripe_sg')
      },
      shopee () {
        return this.providersData.find(p => p.payment_provider === 'shopee_pay')
      }
    },
    methods: {
      onPaynowSaved (event) {
        const data = {
          payment_provider_account_id: `${event.bank_swift_code}@${event.bank_account_no}`,
          data: {
            company: {
              uen: event.company_uen,
              name: event.company_name
            },
            account: {
              name: event.bank_account_name
            }
          }
        }

        if (!this.business.verified_wit_my_info_sg) {
          window.location.href = this.getDomain('business/' + this.business.id + '/verification', 'dashboard')
        }

        const provider = this.providersData.find(p => p.payment_provider === 'dbs_sg')

        if (provider) {
          merge(provider, data)
        } else {
          // PayNow created first time
          this.providersData.push({
            payment_provider: 'dbs_sg',
            ...data
          })

          // Hide global alert, since it is in other Vue instance this is the only way
          const el = document.querySelector('#globalAlert')

          if (el) {
            el.style.display = 'none'
          }
        }
      },
      onMessage ({ text, success }) {
        this.message = text
        this.messageClass = success ? 'alert-success' : 'alert-danger'
      },
      paymentMethodConnected (method) {
        return this.providersData.find(p => p.payment_provider === method)
      },
      removeMethod (method) {
        this.providersData.filter(provider => provider.payment_provider !== method)
      },
      onStripeRemove () {
        if (this.business.country === 'sg') {
          this.removeMethod('stripe_sg')
        } else if (this.business.country === 'my') {
          this.removeMethod('stripe_my')
        } else {
          this.removeMethod('stripe_us')
        }

        this.page = ''
      },
      onShopeeRemoved () {
        this.removeMethod('shopee_pay');
        this.page = ''
      },
      onUpdate (data) {
        console.log(data)
      },
      requirePaynow (method) {
        return (method.slug === 'shopee_pay' || method.slug === 'hoolah' || method.slug === 'grabpay') && !Boolean(this.paynow)
      },
      status (slug) {
        const provider = this.paymentMethodConnected(slug)

        if (provider) {
          switch (this.paymentMethodConnected(slug).onboarding_status) {
            case 'pending_submission':
            case 'pending_verification':
              var default_label = '<span class="on-review">Pending Approval</span>';

              if (
                ['stripe_sg', 'stripe_my', 'stripe_us'].includes(this.paymentMethodConnected(slug).payment_provider) &&
                this.paymentMethodConnected(slug).payment_provider_account_type == 'custom') {
                default_label = this.getStripeStatusLabel(this.paymentMethodConnected(slug));
              }

              return default_label
            case 'rejected':
              return '<span class="rejected">Rejected</span>'
            case 'success':
              var default_label = '';

              if (
                ['stripe_sg', 'stripe_my', 'stripe_us'].includes(this.paymentMethodConnected(slug).payment_provider) &&
                this.paymentMethodConnected(slug).payment_provider_account_type == 'custom') {
                default_label = this.getStripeStatusLabel(this.paymentMethodConnected(slug));
              }

              return default_label
            default:
              return ''
          }
        } else {
          return ''
        }
      },

      getStripeStatusLabel(paymentProvider) {
        let payout_enabled = false;
        let charge_enabled = false;

        if (typeof paymentProvider.data == 'undefined') {
          return '<span class="on-review">Pending Approval</span>';
        }

        if (typeof paymentProvider.data.account == 'undefined') {
          return '<span class="on-review">Pending Approval</span>';
        }

        let data = paymentProvider.data.account;

        if (typeof data.charges_enabled !== "undefined") {
          charge_enabled = data.charges_enabled;
        }

        if (typeof data.payouts_enabled !== "undefined") {
          payout_enabled = data.payouts_enabled;
        }

        let label_charge_enable = charge_enabled ? 'Payments: <b class="text-info">Enabled</b>' : 'Payments: <b class="text-warning">Disabled</b>';
        let label_payout_enable = payout_enabled ? 'Payout: <b class="text-primary">Enabled</b>' : 'Payout: <b class="text-warning">Disabled</b>';

        if (charge_enabled && payout_enabled) {
          return '';
        } else {
          return label_charge_enable + " - " + label_payout_enable;
        }
      }
    },

    mounted() {
      // set stripe to each country, better later on backend
      if (this.business.country === 'sg') {
        this.supportedPaymentProviders.push(
          {
            title: 'PayNow',
            logo: '/icons/payment-providers/paynow.png',
            slug: 'dbs_sg',
            link: 'paynow',
            color: '#FFFFFF',
            requireBusinessVerification: false,
            availableMethods: ['paynow']
          },
          //{ title: 'Hoolah', logo: '/icons/payment-providers/hoolah.png', slug: 'hoolah', link: 'hoolah', color: '#D62E2E', height: 16 }
        );


        let stripeSg = this.providers.find((item) => item.payment_provider === 'stripe_sg');
        if (stripeSg && stripeSg.payment_provider_account_type === 'custom') {
          this.supportedPaymentProviders.push(
            {
              title: 'Cards and Alipay',
              logo: '/icons/payment-providers/credit-cards.png',
              slug: 'stripe_sg',
              link: 'stripe',
              color: '#FFFFFF',
              height: 50,
              requireBusinessVerification: false,
              availableMethods: ['visa', 'master', 'amex', 'alipay', 'apple', 'unionpay', 'googlepay']
            },
          )
        } else  {
          this.supportedPaymentProviders.push(
            {
              title: 'Stripe',
              logo: '/icons/payment-providers/stripe.png',
              slug: 'stripe_sg',
              link: 'stripe',
              color: '#635BFF',
              requireBusinessVerification: false,
              availableMethods: ['visa', 'master', 'amex', 'wechat', 'alipay', 'apple', 'unionpay', 'googlepay']
            }
          );
        }

        this.supportedPaymentProviders.push(
          {
            title: 'GrabPay',
            logo: '/icons/payment-providers/grabpay.svg',
            slug: 'grabpay',
            link: 'grabpay',
            requireBusinessVerification: true,
            availableMethods: ['grabpay', 'grab_paylater']
          },
          {
            title: 'Shopee Pay',
            logo: '/icons/payment-providers/shopee.png',
            slug: 'shopee_pay',
            link: 'shopee',
            color: '#EE4D2A',
            requireBusinessVerification: true,
            availableMethods: ['shopee']
          },
          {
            title: 'Zip',
            logo: '/icons/payment-providers/zip.png',
            slug: 'zip',
            link: 'zip',
            color: 'rgb(65, 23, 96)',
            requireBusinessVerification: true,
            availableMethods: ['zip']
          },
        )
      } else if (this.business.country === 'my') {
        this.supportedPaymentProviders.push(
          {
            title: 'HitPay Payment Gateway',
            logo: '/icons/payment-providers/hitpay.svg',
            slug: 'stripe_my',
            link: 'stripe',
            color: '#FFFFFF',
            width: 55,
            requireBusinessVerification: false,
            availableMethods: ['visa', 'master', 'googlepay', 'apple', 'unionpay', 'fpx', 'alipay', 'grabpay']
          },
        );
      } else {
        let stripeUs = this.providers.find((item) => item.payment_provider === 'stripe_us');
        if (stripeUs && stripeUs.payment_provider_account_type === 'custom') {
          this.supportedPaymentProviders.push(
            {
              title: 'Cards',
              logo: '/icons/payment-providers/credit-cards.png',
              slug: 'stripe_us',
              link: 'stripe',
              color: '#FFFFFF',
              height: 50,
              requireBusinessVerification: false,
              availableMethods: ['visa', 'master', 'amex', 'apple', 'unionpay', 'googlepay']
            },
          )
        }
      }
    }
  }
</script>

<style lang="scss">
  .payment-methods {
    .caption {
      font-size: 14px;
      font-weight: 500;
    }

    .icon-container {
      width: 63px;
      height: 63px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, .2);
      border-radius: 6px;
    }

    .view-details, button.connect {
      height: 40px;
      width: 142px;
      font-size: 14px;
    }

    .view-details {
      color: #5D9DE7;
      background-color: white;
      cursor: pointer;
    }

    .on-review {
      font-size: 14px;
      font-weight: bold;
      color: orange;

      &.rejected {
        color: red;
      }
    }

    .connect {
      color: white;
      background-color: #5D9DE7;
      border-radius: 2px;
      border: 0;
      font-weight: 500;
    }

    .available-methods {
      span {
        font-size: 14px;
        color: #4A4A4A;
      }

      .method {
        height: 22px;
        border: 1px solid lightgrey;
        overflow: hidden;
        padding: 2px 4px;
        border-radius: 2px;
        margin: 1px 0;

        &.full {
          border: none;
          padding: 0px;

          img {
            height: 100%;
          }
        }

        &.full-border {
          padding: 0px;

          img {
            height: 100%;
          }
        }

        img {
          height: calc(100% - 4px);
        }
      }
    }

    .method-info {
      font-size: 14px;
      color: #4A4A4A;
    }

    .require-paynow {
      width: 130px;
      font-size: 14px;
      text-transform: uppercase;
      text-align: center;
    }

    .not-available {
      width: 250px;
      font-size: 13px;
    }
  }
</style>
