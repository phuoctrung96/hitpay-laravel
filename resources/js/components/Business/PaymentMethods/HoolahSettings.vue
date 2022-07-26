<template>
  <div class="hoolah-page p-4 d-flex flex-column justify-content-center">
    <h2 class="text-primary mb-3">{{ hoolahConnected ? 'Connected to Hoolah' : 'Set up Hoolah' }}</h2>

    <Alert
      v-model="message"
      :error="error"/>

    <div class="form-group">
        <label for="business_name" class="small text-secondary">Business name</label>
        <input id="business_name" v-model="business.name" class="form-control" disabled/>
    </div>

    <div class="form-group">
        <label for="email" class="small text-secondary">Email</label>
        <input id="email" v-model="business.email" class="form-control" disabled/>
    </div>

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
        <label for="store_url" class="small text-secondary">Store URL</label>
        <input id="store_url" v-model="form.store_url" class="form-control" :class="{
            'is-invalid': errors.store_url,
        }" autocomplete="off" :disabled="disabled">
        <span class="invalid-feedback" role="alert">{{ errors.store_url }}</span>
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

    <template v-if="hoolahConnected">
      <div class="hr"/>

      <p class="font-weight-bold text-danger mt-4">Remove Hoolah Account</p>

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
        @click="onRemoveHoolah">
        Remove Hoolah Account <i v-if="isProcessing" class="fas fa-circle-notch fa-spin"></i>
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
    provider: Object
  },
  data () {
    return {
      business: window.Business,
      // Shopee data if any
      hoolahConnected: false,
      edit: false,
      form: {
        store_name: '',
        company_uen: '',
        address: '',
        postal_code: '',
        store_url: ''
      },
      isProcessing: false,
      message: '',
      error: false,
      errors: {}
    }
  },
  computed: {
    saveButtonTitle () {
      return this.hoolahConnected
        ? this.edit
          ? 'Save Hoolah Account'
          : 'Edit Hoolah Account'
        : 'Setup Hoolah Pay'
    },
    disabled () {
      return !this.edit || this.isProcessing
    }
  },
  async mounted () {
    this.hoolahConnected = Boolean(this.provider)
    this.edit = !this.hoolahConnected

    if (this.hoolahConnected) {
      this.form = this.provider.data
    } else {
      this.clearForm()
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
          formData.append('address', this.form.address)
          formData.append('postal_code', this.form.postal_code)
          formData.append('password', this.form.password)
          formData.append('store_url', this.form.store_url)

          const res = await axios.post(this.getDomain(`business/${this.business.id}/payment-provider/hoolah/`, 'dashboard'),
            formData,
            {
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
              }
            }
          )

          this.form.password = ''
          this.hoolahConnected = true
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
    async onRemoveHoolah () {
      this.isProcessing = true

      try {
        this.errors = {}

        await axios.post(this.getDomain(`business/${this.business.id}/payment-provider/hoolah/remove`, 'dashboard'), {
          password: this.form.password
        })

        this.hoolahConnected = false
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
        address: '',
        postal_code: '',
        store_url: ''
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.hoolah-page {
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


