<template>
  <div class="grabpay-page p-4 d-flex flex-column justify-content-center">
    <h2 class="text-primary mb-3">{{ pageTitle }}</h2>

    <Alert
      v-model="message"
      :error="error"/>

    <div class="form-group">
        <label for="company_uen" class="small text-secondary">Enter Company UEN or Individual NRIC</label>
        <input id="company_uen" v-model="form.company_uen" class="form-control" :class="{
            'is-invalid': errors.company_uen,
        }" autocomplete="off" :disabled="disabled">
        <span class="invalid-feedback" role="alert">{{ errors.company_uen }}</span>
    </div>

    <div class="form-group">
        <label for="city" class="small text-secondary">City</label>
        <input id="city" v-model="form.city" class="form-control" :class="{
            'is-invalid': errors.city,
        }" autocomplete="off" :disabled="disabled">
        <span class="invalid-feedback" role="alert">{{ errors.city }}</span>
    </div>

    <div class="form-group">
        <label for="address" class="small text-secondary">Full address</label>
        <input id="address" v-model="form.address" class="form-control" :class="{
            'is-invalid': errors.address,
        }" autocomplete="off" :disabled="disabled">
        <span class="invalid-feedback" role="alert">{{ errors.address }}</span>
    </div>

    <div class="form-group">
        <label for="postal_code" class="small text-secondary">Postal code</label>
        <input id="postal_code" v-model="form.postal_code" class="form-control" :class="{
            'is-invalid': errors.postal_code,
        }" autocomplete="off" :disabled="disabled">
        <span class="invalid-feedback" role="alert">{{ errors.postal_code }}</span>
    </div>

    <div class="form-group">
      <label for="postal_code" class="small text-secondary">Merchant category</label>

      <select
        v-model="form.merchant_category_code"
        :disabled="disabled"
        class="custom-select">
        <option
          v-for="opt in business_categories"
          :key="opt.id"
          :value="opt.code">
          {{ opt.category }}
        </option>
      </select>

      <span class="invalid-feedback" role="alert">{{ errors.merchant_category_code }}</span>
    </div>

    <CustomisationCheckBox
      v-model="form.has_grabpay"
      :name="'Do you have an existing GrabPay Merchant ID?'"
      :disabled="disabled"/>

    <div
      v-if="form.has_grabpay"
      class="grab-notify">
      You will be receiving an email from HitPay shortly. Please proceed to submit the form.
    </div>

    <div class="form-group mt-1">
        <label for="password" class="small text-secondary">Enter Your HitPay Account Password</label>
        <input id="password" type="password" v-model="form.password" class="form-control" :class="{
            'is-invalid': errors.password,
        }" autocomplete="off" :disabled="disabled">
        <span class="invalid-feedback" role="alert">{{ errors.password }}</span>
    </div>

    <button
      class="btn btn-primary btn-sm"
      :disabled="saveButtonDisabled"
      @click="onSave(true)">
      {{ saveButtonTitle }} <i v-if="isProcessing" class="fas fa-circle-notch fa-spin"></i>
    </button>

    <template v-if="grabpayConnected">
      <div class="hr"/>

      <p class="font-weight-bold text-danger mt-4">Remove GrabPay Account</p>

      <div class="form-group">
          <label for="validationCustomUsername" class="small">Password</label>

          <input
            v-model="form.password"
            type="password"
            name="password"
            class="form-control bg-light"
            :class="{ 'is-invalid': errors.password }"
            id="password"
            required>

          <span class="invalid-feedback" role="alert">{{ errors.password }}</span>
      </div>
      <button
        class="btn btn-danger btn-sm"
        :disabled="isProcessing"
        @click="onRemoveGrabPay">
        Remove GrabPay Account <i v-if="isProcessing" class="fas fa-circle-notch fa-spin"></i>
      </button>
    </template>
  </div>
</template>

<script>
import axios from 'axios'
import Alert from '../../Dashboard/CheckoutCustomization/Alert'
import CustomisationCheckBox from '../../Dashboard/CheckoutCustomization/CustomisationCheckBox'

export default {
  name: 'GrabPaySettings',
  components: {
    Alert,
    CustomisationCheckBox
  },
  props: {
    provider: Object,
    business_categories: Array
  },
  data () {
    return {
      business: window.Business,
      status: '',
      pageTitles: {
        pending_submission: 'Application to GrabPay awaiting delivery',
        pending_verification: 'Application to GrabPay sent',
        success: 'Connected to GrabPay',
        rejected: 'Application rejected'
      },
      edit: false,
      form: {
        company_uen: '',
        logo: null,
        banner: null,
        outlet: '',
        city: '',
        address: '',
        postal_code: '',
        merchant_category_code: '5699', // First code in list
        has_grabpay: false
      },
      isProcessing: false,
      message: '',
      error: false,
      errors: {}
    }
  },
  computed: {
    pageTitle () {
      return this.status
        ? this.pageTitles[this.status]
        : 'Set up GrabPay'
    },

    saveButtonTitle () {
      return this.status
        ? this.edit
          ? 'Save GrabPay Account'
          : 'Edit GrabPay Account'
        : 'Setup GrabPay'
    },

    grabpayConnected () {
      return this.status === 'success'
    },

    disabled () {
      return !this.edit || this.saveButtonDisabled
    },

    saveButtonDisabled () {
      return this.isProcessing || (Boolean(this.status) && this.status !== 'pending_submission' && this.status !== 'rejected')
    }
  },
  async mounted () {
    this.status = this.provider
      ? this.provider.onboarding_status
      : ''

    this.edit = !this.status

    if (this.status) {
      this.form = this.provider.data
    } else {
      this.clearForm()
    }
  },
  methods: {
    async onSave () {
      this.message = ''

      if (this.edit) {
        this.isProcessing = true

        try {
          this.message = ''
          this.errors = {}

          const formData = new FormData()
          formData.append('company_uen', this.form.company_uen)
          formData.append('city', this.form.city)
          formData.append('address', this.form.address)
          formData.append('postal_code', this.form.postal_code)
          formData.append('merchant_category_code', this.form.merchant_category_code)
          formData.append('password', this.form.password)
          formData.append('has_grabpay', this.form.has_grabpay)

          const res = await axios.post(this.getDomain(`business/${this.business.id}/payment-provider/grabpay/`, 'dashboard'),
            formData,
            {
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
              }
            }
          )

          this.form.password = ''
          this.edit = false
          this.status = 'pending_submission'

          this.error = false
          this.message = 'GrabPay integration has been sent for approval. Payment method will be enabled within 7 business days.'

        } catch (error) {
          if (error.response.status === 422) {
            _.forEach(error.response.data.errors, (value, key) => {
                this.errors[key] = _.first(value);
            })
          } else {
            this.message = error
            this.error = true
          }
        }

        this.isProcessing = false
      } else {
        this.edit = true
      }
    },
    async onRemoveGrabPay () {
      this.isProcessing = true

      try {
        this.errors = {}

        await axios.post(this.getDomain(`business/${this.business.id}/payment-provider/grabpay/remove`, 'dashboard'), {
          password: this.form.password
        })

        this.status = ''
        this.clearForm()
        this.edit = true
      } catch (error) {
        if (error.response.status === 422) {
          _.forEach(error.response.data.errors, (value, key) => {
              this.errors[key] = _.first(value);
          })
        } else {
          this.message = error
          this.error = true
        }
      }

      this.isProcessing = false
    },
    clearForm () {
      this.form = {
        company_uen: '',
        city: '',
        address: '',
        postal_code: '',
        merchant_category_code: '5699',
        has_grabpay: false
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.grabpay-page {
  .custom-file {
    margin-bottom: 1rem;
  }

  .hr {
    height: 1px;
    margin: 24px -24px 0 -24px;
    border-top: 1px solid lightgrey;
  }

  .grab-notify {
    font-size: 14px;
  }
}
</style>


