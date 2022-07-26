<template>
  <div class="custom-control custom-switch is-custom-switch">
    <input
      :id="id"
      v-model="intValue"
      type="checkbox"
      class="custom-control-input">

    <label
      :for="id"
      class="custom-control-label">
      {{ intValue ? onText : offText }}
    </label>
  </div>  
</template>

<script>
export default {
  name: 'SmallSwitch',
  props: {
    id: {
      type: String,
      default: 'small-switch'
    },
    value: Boolean,
    onText: String,
    offText: String
  },
  computed: {
    intValue: {
      get () {
        return this.value
      },
      set (newValue) {
        this.$emit('input', newValue)
      }
    }
  }
}
</script>
