<template>
  <component    
    :is="href ? 'a' : 'div'"
    :href="href"    
    class="small-button d-flex align-items-center justify-content-center"
    :class="{ disabled, [color]: color }"
    :style="styles"
    @click="onClick">
    <i
      v-if="icon"
      class="mr-2"
      :class="icon"/>{{ title }}
  </component>
</template>

<script>
export default {
  name: 'SmallButton',
  props: {
    title: String,
    icon: String,
    href: {
      type: String,
      default: ''
    },
    width: {
      type: Number,
      default: 170
    },
    disabled: {
      type: Boolean,
      default: false
    },
    color: {
      type: String,
      default: ''
    }
  },
  computed: {
    styles () {
      return {
        width: this.width + 'px'
      }
    }
  },
  methods: {
    onClick () {
      if (!this.disabled && !this.href) {
        this.$emit('click')
      }
    }
  }
}
</script>

<style lang="scss" scoped>
$smallButtonColor: #262654;
$redColor: tomato;
$blueColor: #5D9DE7;

.small-button {
  background-color: $smallButtonColor;
  color: white;
  border-radius: 6px;
  border: 0;
  height: 24px;
  font-size: 12px;
  cursor: pointer;

  &:hover {
    color: darken(white, 10%) !important;
    background-color: darken($smallButtonColor, 10%);
  }

  &.red {
    background-color: darken($redColor, 10%);

    &:hover {
      color: darken(white, 10%) !important;
      background-color: darken($redColor, 10%);
    }
  }

  &.blue {
    background-color: darken($blueColor, 10%);

    &:hover {

      background-color: darken($blueColor, 10%);
    }
  }  

  &.disabled {
    color: darken(white, 10%) !important;
    background-color: lightgrey;
    cursor: default;

    &:hover {
      background-color: lightgrey !important;
    }
  }
}
</style>