<template>
  <div class="checkout-customisation d-flex justify-content-center">
    <div class="d-flex flex-column ">
      <div class="dashboard-card d-flex flex-column align-items-start">
        <h2 class="text-primary ml-3 mb-2">Select your theme</h2>

        <Alert
          v-model="messageTheme"
          :error="errorTheme"/>

        <div class="d-flex justify-content-center mt-2">
          <ThemePreview
            v-for="(t, index) in themes"
            :key="index"
            :theme="t"
            :current="theme"
            :custom="t.custom"
            @theme="theme = $event"
            @color="onChangeCustomColor"
            />
        </div>

        <CheckoutButton
          class="save-button mt-4"
          title="Save"
          :disabled="!changedTheme || savingTheme"
          :spinner="savingTheme"
          @click="onSaveTheme"/>
      </div>

      <div class="dashboard-card d-flex flex-column align-items-start mt-4">
        <span class="methods-title mb-2">Payment Methods Order</span>

        <Alert
          v-model="messageMethodOrder"
          :error="errorMethodOrder"/>

        <div class="default-method text-center mt-2">
          Default
        </div>

        <div
          class="my-3"
          @mousemove="onMoveDrag"
          @mouseup="onEndDrag"
          @mouseleave="onEndDrag">

          <transition-group
            name="flip-list"
            class="d-flex"
            tag="div">

            <DraggableMethod
              v-for="(method) in availableMethods"
              :key="method"
              :value="method"
              :dragged="method === dragMethod"
              @startDrag="onStartDrag"/>
          </transition-group>
        </div>

        <span class="methods-text">Drag to arrange the payment methods</span>

        <CheckoutButton
          class="save-button mt-4"
          title="Save"
          :disabled="!changedMethodOrder || savingMethodOrder"
          :spinner="savingMethodOrder"
          @click="onSaveMethodOrder"/>
      </div>

      <div class="dashboard-card d-flex flex-column align-items-start mt-4">
        <span class="methods-title mb-2">Payment Method Rules</span>

        <Alert
          v-model="messageRules"
          :error="errorRules"/>

        <div class="d-flex flex-column mt-2 align-items-center w-100">
          <div
            v-for="(rule, index) in amountRules"
            :key="index"
            class="d-flex align-items-start justify-content-between mb-4">

            <div class="d-flex flex-column first-column">
              <span class="methods-subtitle">Amount</span>

              <div class="d-flex mt-2">
                <NumberInput
                  v-model="rule.options.min"
                  :min="0"
                  label="Min $"
                  :error="amountRuleErrors(rule).min && saved"
                  class="mr-3"/>

                <div class="d-flex flex-column">
                  <NumberInput
                    v-model="rule.options.max"
                    :min="1"
                    label="Max $"
                    :error="amountRuleErrors(rule).max && saved"
                    class="mr-3"/>
                  <span class="optional">Optional</span>
                </div>
              </div>
            </div>

            <div class="d-flex flex-column mx-5">
              <span
                class="methods-subtitle"
                :class="{ 'text-red': amountRuleErrors(rule).methods && saved }">Methods</span>

              <MethodsSelector
                v-model="rule.methods"
                :all="customisation.all_methods"
                :allSort="availableMethods"
                class="mr-3"
                @checked="rule.enabled = true"/>
            </div>

            <div class="d-flex flex-column justify-content-between h-100">
              <CustomisationCheckBox
                v-model="rule.enabled"
                textLeft
                :height="22"
                name="Enable"/>

              <SmallButton
                title="Delete"
                icon="fas fa-trash"
                :width="80"
                color="red"
                :disabled="amountRules.length <= 1"
                @click="onDeleteAmountRule(index)"/>
            </div>
          </div>

          <SmallButton
            title="New amount rule (up to 3)"
            class="mb-2"
            icon="fas fa-plus"
            :width="200"
            :disabled="amountRules.length >= 3"
            @click="onAddAmountRule"/>

          <div class="rule-divider my-4 w-100"/>

          <div class="d-flex align-items-start justify-content-between">
            <div class="d-flex flex-column first-column">
              <span class="methods-subtitle">Device Type</span>

              <div class="d-flex flex-column mt-2">
                <label for="device-type">Select</label>

                <select
                  v-model="deviceRule.options.type"
                  name="device-type">
                  <option value="mobile">Mobile</option>
                  <option value="desktop">Desktop</option>
                </select>
              </div>
            </div>

            <div class="d-flex flex-column mx-5">
              <span
                class="methods-subtitle"
                :class="{ 'text-red': deviceRuleErrors.ruleDeviceMethods && saved }">Methods</span>

              <MethodsSelector
                v-model="deviceRule.methods"
                :all="customisation.all_methods"
                :allSort="availableMethods"
                class="mr-3"
                @checked="deviceRule.enabled = true"/>
            </div>

            <CustomisationCheckBox
              v-model="deviceRule.enabled"
              textLeft
              :height="22"
              name="Enable"/>
          </div>
        </div>

        <CheckoutButton
          class="save-button mt-4"
          title="Save"
          :disabled="!changedRules || savingRules || (!validInput && saved)"
          :spinner="savingRules"
          @click="onSaveRules"/>
      </div>
    </div>
  </div>
</template>

<script>
import { isEqual, cloneDeep } from 'lodash'
import ThemePreview from './ThemePreview'
import CheckoutButton from '../../Shop/CheckoutButton'
import GetTextColor from '../../../mixins/GetTextColor'
import DraggableMethod from './DraggableMethod'
import MethodsSelector from './MethodsSelector'
import CustomisationCheckBox from './CustomisationCheckBox'
import NumberInput from './NumberInput'
import Alert from './Alert'
import SmallButton from '../../UI/SmallButton'

function getNewAmountRule (all) {
  return {
    enabled: false,
    options: {
      min: 0,
      max: 100
    },
    methods: [ ...all ]
  }
}

export default {
  name: 'CheckoutCustomisation',
  components: {
    ThemePreview,
    CheckoutButton,
    DraggableMethod,
    MethodsSelector,
    CustomisationCheckBox,
    NumberInput,
    Alert,
    SmallButton
  },
  mixins: [
    GetTextColor
  ],
  props: {
    business: Object,
    customisation: Object
  },
  data () {
    return {
      themes: [
        {
          title: 'HitPay default',
          value: 'hitpay',

          leftPanelBack: '#011B5F',
          leftPanelFore: 'white',
          leftPanelFore2: 'white',
          buttonBack: '#011B5F',
          buttonFore: 'white'
        },
        {
          title: 'HitPay Light',
          value: 'light',
          leftPanelBack: 'white',
          leftPanelFore: 'black',
          leftPanelFore2: '#545454',
          buttonBack: '#011B5F',
          buttonFore: 'white'
        },
        this.getCustomTheme(this.customisation.tint_color)
      ],
      originalTheme: this.customisation.theme,
      originalCustomColor: this.customisation.tint_color,
      originalMethods: this.customisation.payment_order,
      originalRules: this.customisation.method_rules,
      theme: this.customisation.theme,
      customColor: this.customisation.tint_color,

      savingTheme: false,
      savingMethodOrder: false,
      savingRules: false,

      errorTheme: false,
      errorMethodOrder: false,
      errorRules: false,

      messageTheme: '',
      messageMethodOrder: '',
      messageRules: '',

      saved: false,

      methodRules: {
        amount: [ getNewAmountRule(this.customisation.all_methods) ],
        device: [{
          enabled: false,
          options: {
            type: 'mobile'
          },
          methods: [ ...this.customisation.all_methods ]
        }]
      },
      availableMethods: [],
      dragMethod: '',
      dragMethodX: 0
    }
  },
  computed: {
    changedTheme () {
      return this.originalTheme !== this.theme ||
        this.originalCustomColor !== this.customColor
    },
    changedMethodOrder () {
      return !isEqual(this.originalMethods, this.availableMethods)
    },
    changedRules () {
      return !isEqual(this.originalRules, this.methodRules)
    },
    validInput () {
      const amountRulesValid = this.amountRules.reduce((acc, rule) => {
        const ruleErrors = this.amountRuleErrors(rule)

        return acc && !Object.keys(ruleErrors).reduce((acc, key) => {
          return acc || ruleErrors[key]
        }, false)
      }, true) && this.deviceRule.methods.length > 0

      return Object.keys(this.deviceRuleErrors).reduce((acc, value) => {
          return acc && !this.deviceRuleErrors[value]
        }, true) && amountRulesValid
    },
    deviceRuleErrors () {
      return {
        ruleDeviceMethods: this.deviceRule.methods.length <= 0
      }
    },
    // Return amount rules array
    amountRules () {
      return this.methodRules.amount
    },
    // Return device rule
    deviceRule () {
      return this.methodRules.device[0]
    }
  },
  mounted () {
    this.availableMethods = this.checkMethodsArray(this.customisation.payment_order, this.customisation.all_methods)

    if (this.customisation.method_rules &&
      Array.isArray(this.customisation.method_rules.amount) &&
      Array.isArray(this.customisation.method_rules.device)) {
      this.methodRules = this.customisation.method_rules
    }

    this.originalRules = cloneDeep(this.methodRules)
  },
  methods: {
    getCustomTheme (color) {
      return {
        title: 'Custom Color',
        value: 'custom',
        custom: true,
        leftPanelBack: color,
        leftPanelFore: this.getTextColor(color),
        leftPanelFore2: this.getTextColor(color),
        buttonBack: color,
        buttonFore: this.getTextColor(color)
      }
    },
    onChangeCustomColor (color) {
      this.customColor = color

      // replace custom theme object
      const idx = this.themes.findIndex(t => t.value === 'custom')
      this.$set(this.themes, idx, this.getCustomTheme(color))
    },
    async onSaveTheme () {
      this.savingTheme = true

      try {
        this.errorTheme = false
        this.messageTheme = ''

        await axios.patch(this.getDomain(`business/${this.business.id}/customisation`, 'dashboard'), {
          theme: this.theme,
          customColor: this.customColor
        })

        this.originalTheme = this.theme
        this.originalCustomColor = this.customColor
        this.messageTheme = 'Customisation options saved successfully'

      } catch (error) {
        this.messageTheme = error
        this.errorTheme = true
      }

      this.savingTheme = false
    },
    async onSaveMethodOrder () {
      this.savingMethodOrder = true

      try {
        this.errorMethodOrder = false
        this.messageMethodOrder = ''

        await axios.patch(this.getDomain(`business/${this.business.id}/customisation`, 'dashboard'), {
          payment_order: this.availableMethods
        })

        this.originalMethods = cloneDeep(this.availableMethods)
        this.messageMethodOrder = 'Payment methods order saved successfully'

      } catch (error) {
        this.messageMethodOrder = error
        this.errorMethodOrder = true
      }

      this.savingMethodOrder = false
    },
    async onSaveRules () {
      this.saved = true

      if (this.validInput) {
        this.savingRules = true

        try {
          this.errorRules = false
          this.messageRules = ''

          await axios.patch(this.getDomain(`business/${this.business.id}/customisation`, 'dashboard'), {
            method_rules: JSON.stringify(this.methodRules)
          })

          this.originalRules = cloneDeep(this.methodRules)
          this.saved = false
          this.messageRules = 'Payment method rules saved successfully'

        } catch (error) {
          this.messageRules = error
          this.errorRules = true
        }

        this.savingRules = false
      }
    },
    onStartDrag ({ method, event }) {
      this.dragMethod = method
      this.dragMethodX = event.screenX
    },
    swapMethods (currentIndex, delta) {
      let arr = [ ...this.availableMethods ]

      const t = arr[currentIndex]
      arr[currentIndex] = arr[currentIndex + delta]
      arr[currentIndex + delta] = t

      this.availableMethods = arr
    },
    onMoveDrag (event) {
      if (this.dragMethod) {
        const delta = event.screenX - this.dragMethodX

        if (Math.abs(delta) > 50) {
          const currentIndex = this.availableMethods.indexOf(this.dragMethod)

          if (delta > 0 && currentIndex < (this.availableMethods.length - 1)) {
            this.swapMethods(currentIndex, 1)
          } else if (delta < 0 && currentIndex > 0) {
            this.swapMethods(currentIndex, -1)
          }

          this.dragMethodX = event.screenX
        }
      }
    },
    onEndDrag () {
      this.dragMethod = ''
      this.dragMethodX = 0
    },
    // Check methods array
    // add new ones to the end
    // remove unsupported ones
    checkMethodsArray (arr, all) {
      let res = [ ...arr ]

      all.forEach(m => {
        if (!res.includes(m)) {
          res.push(m)
        }
      })

      return res.filter(m => all.includes(m))
    },
    validateInt (int, min, required = true) {
      return (!required && int === '') ||
        (int !== '' && !isNaN(int) && !isNaN(min) && Number(int) >= Number(min))
    },
    amountRuleErrors (rule) {
      return {
        min: !this.validateInt(rule.options.min, 0),
        max: !this.validateInt(rule.options.max, rule.options.min, false),
        methods: rule.methods.length <= 0
      }
    },
    onAddAmountRule () {
      this.amountRules.push(getNewAmountRule(this.customisation.all_methods))
    },
    onDeleteAmountRule (index) {
      this.amountRules.splice(index, 1)
    }
  }
}
</script>

<style lang="scss" scoped>
.checkout-customisation {
  color: #4A4A4A;
  .first-column {
    width: 170px;
  }

  .default-method {
    color: black;
    background-color: rgb(164, 175, 197);
    border-radius: 2px;
    padding: 2px 24px;
    font-size: 12px;
    width: 110px;
  }

  select {
    border: 1px solid #9B9B9B;
    padding: 4px 6px;
    border-radius: 4px;
  }

  .optional {
    font-size: 12px;
    color: #9B9B9B;
  }

  .rule-divider {
    height: 1px;
    background-color: #979797;
  }

  .methods-title {
    font-size: 18px;
    font-weight: 500;
  }

  .methods-subtitle {
    font-size: 16px;
    font-weight: 500;
  }

  .methods-text {
    font-size: 11px;
  }

  .first-column {
    width: 170px;
  }

  .default-method {
    color: black;
    background-color: rgb(164, 175, 197);
    border-radius: 2px;
    padding: 2px 24px;
    font-size: 12px;
    width: 110px;
  }

  select {
    border: 1px solid #9B9B9B;
    padding: 4px 6px;
    border-radius: 4px;
  }

  .optional {
    font-size: 12px;
    color: #9B9B9B;
  }

  .rule-divider {
    height: 1px;
    background-color: #979797;
  }

  .methods-title {
    font-size: 18px;
    font-weight: 500;
  }

  .methods-subtitle {
    font-size: 16px;
    font-weight: 500;
  }

  .methods-text {
    font-size: 11px;
  }

  .save-button {
    align-self: center;
    width: 200px;
  }

  .flip-list-move {
    transition: transform 0.8s ease;
  }
}
</style>
