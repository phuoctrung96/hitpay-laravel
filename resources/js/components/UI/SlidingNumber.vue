<template>
  <div class="sliding-numbers d-flex align-items-center">
    <template v-for="(l, index) in leftDigits">
      <span
        v-if="l === ','"
        :key="`left-${index}`">,</span>

      <SlidingDigit
        v-else      
        :key="`left-${index}`"
        :height="height"
        :value="l"/>
    </template>

    <template v-if="!zeroDecimal">
      <span>.</span>

      <SlidingDigit
        v-for="(r, index) in rightDigits"
        :key="`right-${index}`"
        :height="height"
        :value="r"/>
    </template>
  </div>  
</template>

<script>
import SlidingDigit from './SlidingDigit'

export default {
  name: 'SlidingNumber',
  components: {
    SlidingDigit
  },
  props: {
    value: {
      type: Number,
      required: true
    },
    height: {
      type: Number,
      default: -1      
    },
    zeroDecimal: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    strValue () {
      return this.value.toFixed(2)
    },
    leftDigits () {
      const dot = this.strValue.indexOf('.')

      const digits = dot === 0
        ? [ 0 ]
        : dot > 0
          ? [...this.strValue.slice(0, dot)].map(c => Number(c))
          : [...this.strValue].map(c => Number(c))

      let res = []
      let j = 0

      for (let i = digits.length - 1, j = 0; i >= 0; i--, j++) {
        if (j > 0 && j % 3 === 0) {
          res.splice(0, 0, ',')  
        }

        res.splice(0, 0, digits[i])
      }

      return res
    },
    rightDigits () {
      const dot = this.strValue.indexOf('.')
      return [...this.strValue.slice(dot + 1)].map(c => Number(c))
    }
  }
}
</script>

<style lang="scss">
.sliding-numbers {
  display: flex;
  line-height: 1;
}
</style>