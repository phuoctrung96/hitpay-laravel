<template>
  <button
    class="checkout-button"
    :class="{ disabled }"
    :style="styles"
    @click="onClick">
    <span>
      <i
        v-if="spinner"
        class="fas fa-spinner fa-spin mr-2"/>

      {{ title }}
      <!-- Use inline SVG because we need to change it color dynamically -->
      <template v-if="dots">
        <svg xmlns:svg="http://www.w3.org/2000/svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.0" width="11px" height="3px" viewBox="0 0 128 35" xml:space="preserve">
          <g><circle :fill="foreColor" fill-opacity="1" cx="17.5" cy="17.5" r="17.5"/><animate attributeName="opacity" dur="2700ms" begin="0s" repeatCount="indefinite" keyTimes="0;0.167;0.5;0.668;1" values="0.3;1;1;0.3;0.3"/></g>
          <g><circle :fill="foreColor" fill-opacity="1" cx="110.5" cy="17.5" r="17.5"/><animate attributeName="opacity" dur="2700ms" begin="0s" repeatCount="indefinite" keyTimes="0;0.334;0.5;0.835;1" values="0.3;0.3;1;1;0.3"/></g>
          <g><circle :fill="foreColor" fill-opacity="1" cx="64" cy="17.5" r="17.5"/><animate attributeName="opacity" dur="2700ms" begin="0s" repeatCount="indefinite" keyTimes="0;0.167;0.334;0.668;0.835;1" values="0.3;0.3;1;1;0.3;0.3"/></g>
        </svg>
      </template>
    </span>
  </button>    
</template>

<script>
export default {
  name: 'CheckButton',
  props: {
    title: String,
    disabled: Boolean,
    dots: {
      type: Boolean,
      default: false
    },
    backColor: {
      type: String,
      default: '#011B5F'
    },
    backDisabledColor: {
      type: String,
      default: '#011B5F'
    },
    foreColor: {
      type: String,
      default: 'white'
    },
    spinner: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    styles () {
      return {
        'background-color': this.backColor,
        'box-shadow': `${this.backColor}66 0 5px 10px`, // .4 opacity
        color: this.foreColor
      }
    }
  },
  methods: {
    onClick () {
      this.$emit(this.disabled ? 'disabledClick' : 'click')
    }
  }
}
</script>

<style lang="scss" scoped>
.checkout-button {
  @media screen and (max-width: 850px) {
    width: 90%;
    max-width: 340px;
  }

  @media screen and (min-width: 850px) {
    width: 373px;
  }

  border-radius: 23px;
  height: 44px;
  border: 0;
  padding: 0;

  display: flex;
  justify-content: center;
  align-items: center;

  span {
    color: inherit !important;
    font-size: 18px !important;
  }

  &:focus {
    outline: none;
  }

  &.disabled {
    opacity: 0.5;
  }

  svg {
    margin-left: 0px;
    margin-bottom: 6px;
    vertical-align: bottom;
  }
}
</style>