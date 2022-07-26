<template>
  <div class="method-selector d-flex flex-column">
    <CustomisationCheckBox
      v-for="item in all"
      :key="item"
      :value="value.includes(item)"
      :name="methodNames[item]"
      @input="onInput(item, $event)"
      />
  </div>
</template>

<script>
import MethodNamesMixin from './MethodNamesMixin'
import CustomisationCheckBox from './CustomisationCheckBox'

export default {
  name: 'MethodsSelector',
  components: {
    CustomisationCheckBox
  },
  mixins: [
    MethodNamesMixin
  ],
  props: {
    value: Array,
    all: Array,
    allSort: Array
  },
  methods: {
    onInput (item, value) {
      let res = [ ...this.value ]

      if (value) {
        if (!res.includes(item)) {
          res.push(item)

          // Sort
          res = res.sort((a, b) => {
            let iA = this.allSort.indexOf(a)
            let iB = this.allSort.indexOf(b)

            // Move items that are not in payment_order into the end
            iA = iA < 0 ? 999 : iA
            iB = iB < 0 ? 999 : iB

            return iA - iB
          })

          this.$emit('checked')
        }
      } else {
        res = res.filter(el => el !== item)
      }

      this.$emit('input', res)
    }
  }
}
</script>
