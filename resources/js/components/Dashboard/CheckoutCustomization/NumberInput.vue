<template>
  <div class="d-flex flex-column">
    <label v-if="label">{{ label }}</label>

    <input
      :min="min"
      type="number"
      class="number-input"
      pattern="[0-9]*"
      :style="styles"
      :class="{ error }"
      :value="value"
      @keypress="onKeyPress"
      @input="$emit('input', $event.target.value)"/>                    
  </div>
</template>

<script>
export default {
  name: 'NumberInput',
  props: {
    value: [Number, String],
    error: Boolean,
    width: {
      type: Number,
      default: 70
    },
    label: {
      type: String,
      default: ''
    },
    min:{
      type: Number,
      default: 0
    },
    decimal: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    styles () {
      return {
        width: `${this.width}px`
      }
    }
  },
  methods: {
    onKeyPress (event) {
      let res = event.charCode >= 48 && event.charCode <= 57

      if (this.decimal && event.charCode === 46) {
        res = true
      }

      if (!res) {
        event.preventDefault()
      }
    }
  }
}
</script>

<style lang="scss">
.number-input {
  border: 1px solid #9B9B9B;
  padding: 4px 6px;        
  border-radius: 4px;

  &.error, &.error:focus {
    color: red;
    border-color: red;
    outline: none !important;
  }
}
</style>