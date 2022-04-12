<template>
  <div class="business-rates">  
    <div class="provider card-body bg-light p-4 border-top"
      v-for="(p, index) in providersList"
      :key="index">

      <!-- PayNow -->
      <template v-if="p.payment_provider === 'dbs_sg'">
        <p><img src="/icons/payment-methods/paynow.png" height="48"></p>

        <p>
          Company UEN: <span
          class="text-muted">{{ p.data['company']['uen'] || '-' }}</span><br>
          Company Name: <span class="text-muted">{{ p.data['company']['name'] || '-' }}</span>
        </p>

        <p>Payout Receiver: <span
          class="text-muted">{{ p.data['account']['name'] || '-' }}</span><br>
          Payout Bank Name: <span
          class="text-muted">{{ swift_codes[bankSwiftCode(p)] || bankSwiftCode(p) }}</span><br>
          Payout Bank Account: <span class="text-muted">{{ bankAccountNumber(p) }}</span>
        </p>
      </template>

      <!-- Stripe -->
      <template v-if="p.payment_provider === 'stripe_sg' || p.payment_provider === 'stripe_my'">
        <div><i class="fab fa-stripe fa-4x"></i></div>

        <p class="mb-0">Account ID: <span
          class="text-muted">{{ p.payment_provider_account_id }}
        </span></p>

        <p
          v-if="stripeEmail(p)"
          class="text-dark">Detected Stripe Email: <span class="text-muted">
          {{ stripeEmail(p) }}
          </span></p>
      </template>

      <!-- GrabPay -->
      <template v-if="p.payment_provider === 'grabpay'">
        <p><img src="/icons/payment-methods/grabpay2.png" height="24"></p>

        <p>
          Company UEN: <span class="text-muted">{{ p.data['company_uen'] || '-' }}</span><br/>
          Merchant category: <span class="text-muted">{{ p.data['merchant_category_code'] || '-' }}</span>
        </p>
      </template>

      <!-- Shopee Pay -->
      <template v-if="p.payment_provider === 'shopee_pay'">
        <p><img src="/icons/payment-methods/shopee.png" height="24"></p>

        <p>
          Company UEN: <span class="text-muted">{{ p.data['company_uen'] || '-' }}</span><br/>
          Store name: <span class="text-muted">{{ p.data['store_name'] || '-' }}</span><br/>
          Merchant category: <span class="text-muted">{{ p.data['mcc'] || '-' }}</span>
        </p>
      </template>

      <!-- Zip -->
      <template v-if="p.payment_provider === 'zip'">
        <p><img src="/icons/payment-methods/zip.png" height="24"></p>

        <p>
          Company UEN: <span class="text-muted">{{ p.data['company_uen'] || '-' }}</span><br/>
          Store name: <span class="text-muted">{{ p.data['store_name'] || '-' }}</span><br/>
          Merchant category: <span class="text-muted">{{ p.data['mcc'] || '-' }}</span><br/>
          Onboarding status: <span class="text-muted">{{ replaceDashes(p.onboarding_status) }}</span>
        </p>
      </template>

      <!-- Rates -->
      <template v-if="p.rates.length > 0">
        <p class="text-dark">Custom Rates</p>

        <ul>
          <li
            v-for="(r, rateIndex) in p.rates"
            :key="`${index}-rate-${rateIndex}`">
            
            <a class="font-weight-bold text-danger" href="#" data-toggle="modal"
              data-target="#deleteModal" :data-rate-id="r.id"
              data-provider-name="Stripe">X</a> - <span
              class="badge badge-success capitalize">{{ replaceDashes(r.channel) }}</span>
              <span class="badge badge-warning capitalize">{{ replaceDashes(r.method) }}</span>
              <span class="small">Fee: {{ (r.percentage * 100).toFixed(2) }}% + {{ currency.toUpperCase() }} {{ (r.fixed_amount / 100).toFixed(2) }}</span>
          </li>
        </ul>
      </template>

      <p v-if="showSetCustomRate(p)"><a
        class="btn btn-sm btn-primary"
        :href="rateLink(p)">
        Set Custom Rate
      </a></p>

      <a
        v-if="showRemove(p)"
        class="font-weight-bold text-danger"
        href="#"
        data-toggle="modal"
        data-target="#removeStripeAccountModal">
        Remove Stripe Account
      </a>
    </div>
  </div>
</template>

<script>
export default {
  name: 'BusinessRates',
  props: {
    providers: Array,
    business_id: String,
    currency: String,
    allow_remove: Boolean,
    swift_codes: Object
  },
  data () {
    return {
      supportedProviders: [
        'stripe_sg',
        'stripe_my',
        'dbs_sg',
        'grabpay',
        'zip',
        'shopee_pay'
      ],
      allowRemoveProviders: [
        'stripe_sg',
        'stripe_my',
      ],
      translateProviders: {
        'stripe_sg': 'stripe',
        'stripe_my': 'stripe',
        'dbs_sg': 'paynow',
        'grabpay': 'grabpay',
        'zip': 'zip',
        'shopee_pay': 'shopee_pay'
      },
      hasOnboarding: [
        'grabpay',
        'shopee_pay'
      ]
    }
  },
  computed: {
    providersList () {
      return this.providers
        .filter(p => this.supportedProviders.includes(p.payment_provider))
        .filter(p => !this.hasOnboarding.includes(p.payment_provider) || p.onboarding_status === 'success')
        .map(p => ({
          ...p
        }))
    }
  },
  methods: {
    rateLink (p) {
      return `/business/${this.business_id}/payment-provider/${this.translateProviders[p.payment_provider]}/rate`
    },
    showRemove (p) {
      return this.allow_remove && this.allowRemoveProviders.includes(p.payment_provider)
    },
    showSetCustomRate (p) {
      const hasOnboarding = this.hasOnboarding.includes(p.payment_provider)

      return !hasOnboarding || p.onboarding_status === 'success'
    },
    bankSwiftCode (p) {
      return p.payment_provider_account_id.split('@')[0]
    },
    bankAccountNumber (p) {
      const split = p.payment_provider_account_id.split('@')

      return split.length > 1
        ? split[1]
        : split[0]
    },
    stripeEmail (p) {
      let res = p.data['email'] || ''

      if (p.data['support_email']) {
        if (res) {
          res += ' / '
        }

        res += p.data['support_email']
      }

      return res
    },
    replaceDashes (s) {
      return s.replace('_', ' ')
    }
  }
}
</script>

<style lang="scss">
.business-rates {
  .provider {
    .capitalize {
      text-transform: capitalize;
    }
  }
}
</style>