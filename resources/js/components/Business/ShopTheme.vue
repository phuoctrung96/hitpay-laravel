<template>
  <div class="col-12 col-lg-4 theme-container">
    <div class="theme-option card p-4 h-100" :class="{ selected }"
      @click="onClick">
      <div class="ctn-inner">
        <div class="option d-flex justify-content-between align-items-center">
          <label class="is-label-checkbox" @click="$refs.color.click()">
             {{ theme.title }}
            <input class="form-check-input" v-model="theme_type" type="radio" name="theme_type" id="default" :value="theme.value" @change="onClick()">
            <span class="checkmark"></span>
          </label>
          <div class="change-color" v-if="theme.custom">
            <span>
              <img src="/images/ico-theme-custom.svg" alt="" @click="$refs.color.click()">
            </span>
            <input
            :value="theme.leftPanelBack"
            ref="color"
            type="color"
            @input="$emit('color', $event.target.value)"/>
          </div>
        </div>
        <div class="thumbnail">
          <div class="preview shadow-sm">
            <div class="top-theme shadow-sm" :style="previewLeftStyles">
              <span class="icon" v-if="!isLight">
              <img src="/images/cart_icon_light.svg" alt="">
            </span>
            <span class="icon" v-else>
              <img src="/images/cart_icon.svg" alt="">
            </span>
            </div>
            <div class="body-theme">
              <div class="opt-item">
                <div class="row">
                  <div class="col-4">
                    <div class="item">
                      <div class="ol"></div>
                      <div class="ft shadow-sm" :style="previewLeftStyles"></div>
                    </div>
                  </div>
                  <div class="col-4">
                    <div class="item">
                      <div class="ol"></div>
                      <div class="ft shadow-sm" :style="previewLeftStyles"></div>
                    </div>
                  </div>
                  <div class="col-4">
                    <div class="item">
                      <div class="ol"></div>
                      <div class="ft shadow-sm" :style="previewLeftStyles">
                        <span class="sq"></span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="theme-pagging clearfix">
                <div class="first page-item clearfix">
                  <div class="inner">
                    <span class="bd" :style="previewLeftStyles"></span>
                  </div>
                </div>
                <ul v-if="!theme.custom">
                  <li :style="previewLeftStyles"><span></span></li>
                  <li :style="previewLeftStyles"><span></span></li>
                  <li :style="previewLeftStyles"><span></span></li>
                </ul>
                <div class="last page-item clearfix">
                  <div class="inner">
                    <span class="bd" :style="previewLeftStyles"></span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>  
  </div>  
</template>

<script>
import Rounded from '../Dashboard/CheckoutCustomization/Rounded'

export default {
  name: 'ShopTheme',
  components: {
    Rounded
  },
  props: {
    theme: Object,
    current: String
  },
  data() {
        return {
          theme_type: ""
        }}
  ,      
  mounted(){
    if(this.theme.value === this.current)
    {
      this.theme_type = this.current
    }
  },
  computed: {
    previewLeftStyles () {
      return {
        'background-color': this.theme.leftPanelBack
      }
    },
    selected () {
      return this.theme.value === this.current
    },
    isLight () {
      return (this.theme.title === 'Light')
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