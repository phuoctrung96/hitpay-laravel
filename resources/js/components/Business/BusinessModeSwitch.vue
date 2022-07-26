<template>
  <div class="business-switch-option d-flex">
    <div v-for="(option, index) in options" :key="index" class="item">
      <label class="label-checkbox">
        <input
          v-model="business_type"
          type="radio"
          :value="option"
          @click="onClick(option)"/> {{ option }}
        <span class="checkmark"></span>
      </label>
    </div>
  </div>
</template>

<script>
export default {
    name: 'BusinessModeSwitch',
    props: {
        options: {
            type: Array,
            default: () => [              
              'company',
              'individual',
            ]
        },
        value: String
    },
    data () {
      return {
        business_type: this.value
      }      
    },
    methods: {
        onClick(option) {
            this.$emit('input', option)
        }
    }
}
</script>

<style lang="scss">
.business-switch-option {
  .item{
      margin: 0px 32px 0px 0px;
  }

  label {
    text-transform: capitalize;
  }
}
</style>
