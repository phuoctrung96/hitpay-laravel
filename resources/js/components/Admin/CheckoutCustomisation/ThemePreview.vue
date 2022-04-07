<template>
  <div class="theme-container">
    <div
      class="theme-preview d-flex flex-column align-items-center"
      :class="{ selected }"
      @click="onClick">

      <CustomisationCheckBox
        :value="selected"/>

      <div class="title my-2 d-flex align-items-center">
        {{ theme.title }}

        <input
          :value="theme.leftPanelBack"
          v-if="theme.custom"
          type="color"
          @input="$emit('color', $event.target.value)"/>
      </div>

      <div class="preview d-flex">
        <div
          class="left d-flex flex-column justify-content-center align-items-center"
          :style="previewLeftStyles">
          <Rounded
            :color="theme.leftPanelFore2"
            width="70%"
            :height="10"/>

          <Rounded
            class="mt-1"
            :color="theme.leftPanelFore2"
            width="45%"
            :height="6"/>

          <Rounded
            class="mt-2"
            :color="theme.leftPanelFore2"
            width="60%"
            :height="10"/>
        </div>

        <div
          class="main d-flex flex-column align-items-center justify-content-end pb-4">

          <div class="qr-code">
            <img src="/images/qr-sample.png"/>
          </div>

          <Rounded
            class="mb-2 mt-3"
            :color="theme.buttonBack"
            width="50px"
            :height="10"/>
        </div>
      </div>
    </div>      
  </div>  
</template>

<script>
import Rounded from './Rounded'
import CustomisationCheckBox from './CustomisationCheckBox'

export default {
  name: 'ThemePreview',
  components: {
    Rounded,
    CustomisationCheckBox
  },
  props: {
    theme: Object,
    current: String
  },
  computed: {
    previewLeftStyles () {
      return {
        'background-color': this.theme.leftPanelBack
      }
    },
    selected () {
      return this.theme.value === this.current
    }
  },
  methods: {
    onClick () {
      if (!this.selected) {
        this.$emit('theme', this.theme.value)
      }
    }
  }
}
</script>

<style lang="scss" scoped>
$width: 150px;
$selector: 8px;

.theme-container {
  &:not(:last-child) {
    padding-right: 32px;
    border-right: solid 1px lightgrey;
  }

  &:not(:first-child) {
    padding-left: 32px;
  }

  .theme-preview {
    border-radius: 8px;
    padding: 8px;
    cursor: pointer;

    &.selected {
      cursor: default;
    }

    &:not(.selected):hover {
      background-color: rgba(0, 0, 0, .05);
    }

    .title {
      height: 30px;

      input[type=color] {
        margin-left: 4px;
        width: 40px;
        height: 20px;
        border: 0;
        background-color: transparent;
        cursor: pointer;

        &:focus {
          outline: none;
        }

        &::-webkit-color-swatch-wrapper {
          padding: 0;
          width: 100%;
          height: 100%;
        }

        &::-webkit-color-swatch {
          border: none;
        }
      }
    }

    .preview {
      width: $width;
      height: $width * 1.2;
      padding: 4px;

      .left {
        flex: 0 0 ($width / 3);
        height: 100%;
        box-shadow: rgba(0, 0, 0, .2) 0px 3px 5px 0px;
        z-index: 2;
      }

      .main {
        border: solid .5px lightgrey;
        border-left: none;
        width: 100%;
        height: 100%;
        background-color: white;

        .qr-code {
          img {
            width: $width / 3;
            height: $width / 3;
          }
        }
      }
    }
  }
}
</style>