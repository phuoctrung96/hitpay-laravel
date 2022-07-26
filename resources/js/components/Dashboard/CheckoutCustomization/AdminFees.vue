<template>
  <div class="dashboard-card admin-fees d-flex flex-column">
    <div class="d-flex admin-fees-inner">
      <div class="left-side flex-grow-1 mr-3">
        <div class="methods-title mb-3">Pass fees to the customer</div>

        <Alert
          v-model="message"
          :error="error"/>

        <p>Hitpay allows you to add an additional fee to your customers based on the payment method selected</p>

        <SmallSwitch
          v-model="options.enabled"
          onText="Admin fees is enabled"
          offText="Admin fees is disabled"/>

        <template v-if="options.enabled">
          <p class="mb-1 mt-3">Which payment method would you want to pass the fees?</p>

          <div class="d-flex align-items-center">
            <input type="radio"
              v-model="options.allMethods"            
              :value="true"
              :disabled="saving">

            <label class="mb-0 ml-2">All Payment Methods</label>

            <input type="radio"
              v-model="options.allMethods"            
              :value="false"            
              :disabled="saving"
              class="ml-4">

            <label class="mb-0 ml-2">Specific Payment Methods</label>
          </div>

          <TagsSelect
            v-if="!options.allMethods"
            v-model="methodKeys"
            :allOptions="availableMethods"
            :optionNames="methodNames"
            hint="Choose a payment method..."
            class="mt-2"/>

          <!-- Custom fees -->

          <p class="mt-4 mb-1">How much fees do you want to charge the customer?</p>

          <div class="d-flex flex-column">
            <div
              v-for="method in methodKeys"
              :key="method"
              class="d-flex fee-row align-items-center mt-2">

              <div class="d-flex name">
                <label class="mb-0 mr-2">{{ methodNames[method] }}</label>
              </div>

              <div class="d-flex flex-column">
                <div class="d-flex align-items-center">
                  <b-form-input
                    :value="options.methods[method]"
                    @input="setFee(method, $event)"
                    type="number"
                    size="sm"
                    class="custom-fee"/>

                  <label class="mb-0 ml-1">%</label>
                </div>

                <div
                  v-if="methodErrors[method] && saved"
                  class="error">
                  {{ methodErrors[method] }}
                </div>
              </div>
            </div>
          </div>

          <p class="mt-4 mb-1">Select the channel</p>

          <TagsSelect
            v-model="options.channels"
            :allOptions="availableChannels"
            :optionNames="channels"
            hint="Choose a channel..."
            class="mt-2"/>

          <p class="mt-4 mb-1">Text to show the customer</p>

          <input
            v-model="options.customText"
            class="form-control"
            maxlength="22"/>
        </template>        
      </div>

      <div
        v-if="options.enabled"
        class="right-side d-flex flex-column flex-shrink-0">
        <p class="example text-center mb-4">Example</p>

        <div class="mb-3 d-flex justify-content-between align-items-center">
          Amount
          <div>
            {{ business.currency.toUpperCase() }} {{ sampleAmountFormatted }}
          </div>
        </div>

        <div class="mb-3 d-flex justify-content-between align-items-center">
          Payment Method
          <i
            v-if="loadingRates"
            class="fas fa-spinner fa-spin mx-4"/>

          <div
            v-else
            class="method-name">

            <b-form-select
              v-model="sampleMethod"
              :options="methodOptions"
              plain
              size="sm"/>
          </div>
        </div>

        <div class="mb-3 d-flex justify-content-between align-items-center">
          Channel
          <i
            v-if="loadingRates"
            class="fas fa-spinner fa-spin mx-4"/>

          <div
            v-else
            class="method-name">

            <b-form-select
              v-model="sampleChannel"
              :options="availableChannelsDropDown"
              plain
              size="sm"/>
          </div>
        </div>

        <div class="mb-3 d-flex justify-content-between align-items-center">
          Amount Paid By Customer

          <i
            v-if="loadingRates"
            class="fas fa-spinner fa-spin mx-4"/>

          <div v-else>
            {{ business.currency.toUpperCase() }} {{ amountWithFeeFormatted }}
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex flex-column align-items-center">
      <CheckoutButton
        class="save-button mt-4 align-self-center"
        title="Save"
        :disabled="saving || !changed || (saved && !valid())"
        :spinner="saving"
        @click="onSave"/>

      <span
        class="mt-1 reset-link"
        role="link"
        @click="onResetToDefault">
        Reset to default
      </span>
    </div>
    
    <div
      id="confirmModal"
      class="modal"
      role="dialog"
      data-backdrop="static">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-body py-3 text-center">
            Do you really want to reset fees settings?

            <div class="d-flex mt-3 justify-content-center">
              <span            
                class="btn btn-primary btn-sm mr-3"
                role="button"
                data-dismiss="modal"
                @click="doResetToDefault">Reset</span>

              <span
                class="btn btn-danger btn-sm"
                role="button"
                data-dismiss="modal">Cancel</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { isEqual, cloneDeep } from 'lodash'
import MethodNames from './MethodNamesMixin'
import CheckoutButton from '../../Shop/CheckoutButton'
import TagsSelect from '../../UI/TagsSelect'
import SmallSwitch from '../../UI/SmallSwitch'
import Alert from './Alert'

const defaultOptions = {
  version: 2,  
  enabled: false,
  allMethods: false,
  methods: {},
  channels: [],
  customText: 'Admin Fees'
}

export default {
  name: 'AdminFees',
  mixins: [
    MethodNames
  ],
  components: {
    CheckoutButton,
    TagsSelect,
    Alert,
    SmallSwitch
  },
  props: {
    business: Object,
    availableMethods: {
      type: Array,
      default: () => []
    },
    adminFeeOptions: Object,
    channels: Object
  },
  watch: {
    'options.allMethods': function (newValue) {
      if (newValue) {
        this.availableMethods.forEach(method => {
          if (!this.options.methods[method]) {
            this.$set(this.options.methods, method, .5)
          }
        })
      }
    },
    availableMethods (newVal, oldVal) {
      if (oldVal.length === 0 && newVal.length > 0) {
        this.sampleMethod = newVal[0]
      }
    },
    changed () {
      this.$emit('changed', this.changed)
    }
  },
  data () {
    return {
      originalOptions: defaultOptions,
      options: cloneDeep(defaultOptions),

      saving: false,
      loadingRates: false,

      sampleAmount: 100,
      sampleMethod: 'paynow_online',
      sampleChannel: 'api_link',

      error: false,
      message: '',
      methodErrors: {},
      //customFeeError: '',
      saved: false
    }
  },
  computed: {
    sampleAmountFormatted () {
      return this.sampleAmount.toFixed(2)
    },
    amountWithFeeFormatted () {
      return this.methodKeys.includes(this.sampleMethod)
        ? Number(this.sampleAmount + this.sampleAmount * (this.options.methods[this.sampleMethod] / 100)).toFixed(2)
        : this.sampleAmount
    },
    methodOptions () {
      return this.availableMethods.map(m => ({
        value: m,
        text: this.methodNames[m]
      }))
    },
    changed () {
      return !isEqual(this.originalOptions, this.options)
    },
    availableChannels () {
      return Object.keys(this.channels)
    },
    availableChannelsDropDown () {
      return this.availableChannels.map(key => ({
        value: key,
        text: this.channels[key]
      }))
    },
    methodKeys: {
      get () {
        return Object.keys(this.options.methods)
      },
      set (newValue) {
        this.methodKeys.forEach(method => {
          if (!newValue.includes(method)) {
            this.$delete(this.options.methods, method)
          }
        })

        newValue.forEach(method => {
          if (!this.options.methods[method]) {
            this.$set(this.options.methods, method, .5)
          }
        })
      }      
    }
  },
  mounted () {
    if (this.adminFeeOptions) {
      this.originalOptions = this.adminFeeOptions

      if (this.originalOptions.customText === undefined) {
        this.originalOptions.customText = 'Admin Fees'
      }
    } else {
      // Default options
      this.originalOptions = defaultOptions
    }

    this.options = cloneDeep(this.originalOptions)

    this.validateFees()
  },
  methods: {
    async onSave () {
      this.saved = true

      if (this.valid()) {
        try {
          this.error = false
          this.message = ''

          await axios.patch(this.getDomain(`business/${this.business.id}/customisation`, 'dashboard'), {
            admin_fee_settings: JSON.stringify(this.options)
          })

          this.originalOptions = cloneDeep(this.options)
          this.message = 'Admin fees settings saved successfully'

        } catch (error) {
          this.message = error
          this.error = true
        }
      }
    },
    validateInt (int, min, max, required = true) {
      return (!required && int === '') ||
        (int !== '' && !isNaN(int) && !isNaN(min) && Number(int) >= Number(min) && Number(int) <= Number(max))
    },
    setFee (method, value) {
      this.$set(this.options.methods, method, Number(value))
      this.validateFees()
    },
    validateFees () {
      this.methodKeys.forEach(key => {
        let res = false
        const value = this.options.methods[key]

        if (!value) {
          res = 'Value is required'
        } else if (!this.validateInt(value, 0.1, 100, true)) {
          res = 'Value should be a number between 0.1 and 100'
        }

        if (res) {
          this.$set(this.methodErrors, key, res)
        } else {
          this.$delete(this.methodErrors, key)
        }
      })
    },
    valid () {
      return !Object.keys(this.methodErrors).some(key => Boolean(this.methodErrors[key]))
    },
    async onResetToDefault () {
      $('#confirmModal').modal('show')
    },
    async doResetToDefault () {
      this.options = cloneDeep(defaultOptions)
      await this.onSave()
    }
  }
}
</script>

<style lang="scss" scoped>
.admin-fees {
  .admin-fees-inner {
    @media screen and (max-width: 992px) {
      flex-wrap: wrap;
    }

    .left-side {
      font-size: 12px;

      .methods-title {
        font-size: 18px;
        font-weight: 500;
      }

      .custom-fee {
        width: 100px;
      }

      .mt10px {
        margin-top: 8px;
      }

      .fee-row {
        .name {
          width: 70px;
          text-transform: capitalize;
        }
      }
    }
    
    .right-side {
      width: 300px;
      background: #E5E5E5;
      box-shadow: inset 0px 4px 4px rgba(0, 0, 0, 0.25);
      border-radius: 6px;
      padding: 16px;
      font-size: 12px;
      font-weight: 400;

      @media screen and (max-width: 992px) {
        margin-top: 16px;
      }

      .example {
        font-size: 16px;
      }

      .method-name {
        background-color: #C4C4C4;

        select {
          background-color: transparent;
          font-size: 12px;

          &:focus {
            outline: none;
            box-shadow: none;
            border-color: transparent;
          }
        }
      }
    }    
  }


  .save-button {
    align-self: center;
    width: 200px;
  }

  .reset-link {
    cursor: pointer;
    font-size: 14px;

    &:hover {
      text-decoration: underline;
    }
  }
}
</style>
