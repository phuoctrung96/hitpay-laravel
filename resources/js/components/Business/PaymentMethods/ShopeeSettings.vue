<template>
  <div class="shopee-page p-4 d-flex flex-column justify-content-center">
    <h2 class="text-primary mb-3">{{ shopeeConnected ? 'Connected to Shopee Pay' : 'Set up Shopee Pay' }}</h2>

    <Alert
      v-model="message"
      :error="error"/>

    <div class="form-group">
        <label for="store_name" class="small text-secondary">Store name</label>
        <input id="store_name" v-model="form.store_name" class="form-control" :class="{
            'is-invalid': errors.store_name,
        }" autocomplete="off" :disabled="disabled" autofocus>
        <span class="invalid-feedback" role="alert">{{ errors.store_name }}</span>
    </div>

    <div class="form-group">
        <label for="company_uen" class="small text-secondary">Enter Company UEN or Individual NRIC</label>
        <input id="company_uen" v-model="form.company_uen" class="form-control" :class="{
            'is-invalid': errors.company_uen,
        }" autocomplete="off" :disabled="disabled">
        <span class="invalid-feedback" role="alert">{{ errors.company_uen }}</span>
    </div>

    <!--
    <div class="custom-file form-group">
        <label for="logo" class="small text-secondary custom-file-label">Logo</label>
        <input id="logo" class="custom-file-input" :class="{
            'is-invalid': errors.logo,
        }" type="file" :disabled="disabled">
    </div>

    <div class="custom-file form-group">
        <label for="banner" class="small text-secondary custom-file-label">Banner</label>
        <input id="banner" class="custom-file-input" :class="{
            'is-invalid': errors.banner,
        }" type="file" :disabled="disabled">
    </div>

    <div class="form-group">
        <label for="outlet" class="small text-secondary">Outlet</label>
        <input id="outlet" v-model="form.outlet" class="form-control" :class="{
            'is-invalid': errors.outlet,
        }" autocomplete="off" :disabled="disabled">
        <span class="invalid-feedback" role="alert">{{ errors.outlet }}</span>
    </div>
    -->

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
        <label for="mcc" class="small text-secondary">MCC</label>

        <select
          v-model="form.mcc"
          :disabled="disabled"
          class="custom-select">
          <option
            v-for="opt in business_categories"
            :key="opt.id"
            :value="opt.code">
            {{ opt.category }}
          </option>
        </select>

        <span class="invalid-feedback" role="alert">{{ errors.mcc }}</span>
    </div>

    <div class="form-group">
        <label for="password" class="small text-secondary">Enter Your HitPay Account Password</label>
        <input id="password" type="password" v-model="form.password" class="form-control" :class="{
            'is-invalid': errors.password,
        }" autocomplete="off" :disabled="disabled">
        <span class="invalid-feedback" role="alert">{{ errors.password }}</span>
    </div>

    <button
      class="btn btn-primary btn-sm"
      :disabled="isProcessing"
      @click="onSave(true)">
      {{ saveButtonTitle }} <i v-if="isProcessing" class="fas fa-circle-notch fa-spin"></i>
    </button>

    <template v-if="shopeeConnected">
      <div class="hr"/>

      <p class="font-weight-bold text-danger mt-4">Remove Shopee Account</p>

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
        @click="onRemoveShopee">
        Remove Shopee Account <i v-if="isProcessing" class="fas fa-circle-notch fa-spin"></i>
      </button>
    </template>
  </div>
</template>

<script>
import axios from 'axios'
import Alert from '../../Dashboard/CheckoutCustomization/Alert'

export default {
  name: 'ShopeeSettings',
  components: {
    Alert
  },
  props: {
    provider: Object,
    uen: String,
    mcc: String,
    verification: Object,
    business_categories: Array
  },
  data () {
    return {
      business: window.Business,
      // Shopee data if any
      shopeeConnected: false,
      edit: false,
      form: {
        store_name: '',
        company_uen: '',
        logo: null,
        banner: null,
        outlet: '',
        city: '',
        address: '',
        postal_code: '',
        mcc: ''
      },
      isProcessing: false,
      message: '',
      error: false,
      errors: {}
    }
  },
  computed: {
    saveButtonTitle () {
      return this.shopeeConnected
        ? this.edit
          ? 'Save Shopee Account'
          : 'Edit Shopee Account'
        : 'Setup Shopee Pay'
    },
    disabled () {
      return !this.edit || this.isProcessing
    }
  },
  async mounted () {
    this.shopeeConnected = Boolean(this.provider)
    this.edit = !this.shopeeConnected

    if (this.shopeeConnected) {
      this.form = this.provider.data
    } else {
      this.clearForm()

      // Prefill business fields
      this.form.store_name = this.business.name
      this.form.company_uen = this.uen
      this.form.outlet = this.business.name

      this.form.mcc = this.mcc

      // Prefill fields from verification data
      const v = this.verification

      if (v && v.my_info_data && v.my_info_data.data && v.my_info_data.data.entity && v.my_info_data.data.entity.addresses &&
        v.my_info_data.data.entity.addresses['addresses-list'] && v.my_info_data.data.entity.addresses['addresses-list'].length > 0) {
        const adr = v.my_info_data.data.entity.addresses['addresses-list'][0]

        this.form.address = adr.street ? adr.street.value : ''
        this.form.postal_code = adr.postal ? adr.postal.value : ''
      }
    }
  },
  methods: {
    async onSave () {
      if (this.edit) {
        this.isProcessing = true

        try {
          this.message = ''
          this.errors = {}

          const formData = new FormData()
          formData.append('store_name', this.form.store_name)
          formData.append('company_uen', this.form.company_uen)
          formData.append('outlet', this.form.outlet)
          formData.append('city', this.form.city)
          formData.append('address', this.form.address)
          formData.append('postal_code', this.form.postal_code)
          formData.append('mcc', this.form.mcc)
          formData.append('password', this.form.password)

          const res = await axios.post(this.getDomain(`business/${this.business.id}/payment-provider/shopee/`, 'dashboard'),
            formData,
            {
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
              }
            }
          )

          this.form.password = ''
          this.shopeeConnected = true
          this.edit = false

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
    async onRemoveShopee () {
      this.isProcessing = true

      try {
        this.errors = {}

        await axios.post(this.getDomain(`business/${this.business.id}/payment-provider/shopee/remove`, 'dashboard'), {
          password: this.form.password
        })

        this.shopeeConnected = false
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
        store_name: '',
        company_uen: '',
        logo: null,
        banner: null,
        outlet: '',
        city: '',
        address: '',
        postal_code: '',
        mcc: ''
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.shopee-page {
  .custom-file {
    margin-bottom: 1rem;
  }

  .hr {
    height: 1px;
    margin: 24px -24px 0 -24px;
    border-top: 1px solid lightgrey;
  }
}
</style>


