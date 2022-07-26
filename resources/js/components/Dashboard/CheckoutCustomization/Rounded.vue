<template>
  <div :style="styles"/>
</template>

<script>
export default {
  name: 'Rounded',
  props: {
    color: String,
    height: Number,
    width: String
  },
  computed: {
    styles () {
      return {
        'background-color': this.color,
        'border-radius': this.height / 2  + 'px',        
        height: this.height + 'px',
        width: this.width
      }
    }
  }
}
</script>
