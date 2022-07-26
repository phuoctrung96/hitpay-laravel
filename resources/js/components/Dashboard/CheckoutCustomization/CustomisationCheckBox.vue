<template>
  <div
    class="d-flex align-items-center customisation-check-box"
    :class="{ disabled }"
    @click="onClick">

    <span
      v-if="textLeft"
      class="label mr-2">{{ name }}</span>

    <svg
      xmlns="http://www.w3.org/2000/svg"
      :height="height"
      viewBox="0 0 22 22">

      <circle
        cx="11"
        cy="11"
        r="11"
        :class="{ checked: value }"
        />

      <path
        v-if="value"
        d="M0,4.5,3.5,8l8-8" transform="translate(5 7)" fill="none" stroke="#fff" stroke-miterlimit="10" stroke-width="2"/>
    </svg>

    <span
      v-if="!textLeft"
      class="label ml-2">{{ name }}</span>
  </div>
</template>

<script>
export default {
  name: 'CustomisationCheckBox',
  props: {
    value: Boolean,
    name: String,
    textLeft: {
      type: Boolean,
      default: false
    },
    height: {
      type: Number,
      default: 16
    },
    disabled: {
      type: Boolean,
      default: false
    }
  },
  methods: {
    onClick () {
      if (!this.disabled) {
        this.$emit('input', !this.value)
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.customisation-check-box {
  cursor: pointer;  

  $color: #007aff;

  circle {
    fill: lightgrey;

    &.checked {
      fill: $color;

      &:hover {
        fill: darken($color, 10%);
      }
    }

    &:hover {
      fill: darken(lightgrey, 10%);
    }
  }

  .label {
    color: black;
  }


  &.disabled {
    cursor: default;  

    circle {
      &.checked {
        fill: lightgrey;

        &:hover {
          fill: lightgrey;
        }
      }

      &:hover {
        fill: lightgrey;
      }
    }

    .label {
      color: #6c757d;
    }
  }
}
</style>
