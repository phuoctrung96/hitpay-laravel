<template>
  <div class="d-flex justify-content-center">
    <div class="onboarding-index">
      <div
        v-for="(p, index) in Object.keys(data)"
        :key="index"
        class="provider d-flex align-items-center">

        <provider-logo :value="p"/>

        <div class="ml-3">
          {{ providerName(p) }}
        </div>

        <div class="flex-grow-1 d-flex justify-content-end">
          <a
            class="review-button d-flex align-items-center justify-content-center"
            :href="`/onboarding/${p}`">
            Pending Onboarding ({{ data[p] }})
          </a>
        </div>
      </div>
    </div>      
  </div>
</template>

<script>
import ProviderNamesMixin from '../../mixins/ProviderNamesMixin'

export default {
  name: 'OnboardingIndex',
  mixins: [
    ProviderNamesMixin
  ],
  props: {
    data: Object
  }
}
</script>

<style lang="scss" scoped>
.onboarding-index {
  width: 500px;

  .review-button {
    border-radius: 4px;
    padding: 4px;
    height: 40px;
    width: 242px;
    font-size: 14px;
    color: white;
    background-color: #5D9DE7;
    cursor: pointer;    
    user-select: none;

    &.disabled {
      pointer-events: none;
      background-color: lightgrey;
    }
  }
}
</style>