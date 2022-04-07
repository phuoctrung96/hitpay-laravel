<template>
  <div
    v-if="method.color"
    
    class="icon-container ml-1 d-flex align-items-center">

    <div
      class="inner d-flex align-items-center justify-content-center"
      :style="{ 'background-color': method.color }">
      <img
        :src="method.logo"
        :height="method.height || 20"
        :width="method.width"
      />
    </div>
  </div>

  <img
    v-else
    :src="method.logo"
    width="72"
    height="72"/>  
</template>

<script>
export default {
  name: 'ProviderLogo',
  props: {
    value: String
  },
  data () {
    return {
      methods: {
        paynow: {
          logo: '/icons/payment-providers/paynow.png',
          color: '#FFFFFF'
        },
        grabpay: {
          logo: '/icons/payment-providers/grabpay.svg'
        },
        shopee_pay: {
          logo: '/icons/payment-providers/shopee.png',
          color: '#EE4D2A'
        },
        zip: {
          logo: '/icons/payment-providers/zip.png',
          color: 'rgb(65, 23, 96)'
        },
        stripe: {
          logo: '/icons/payment-providers/stripe.png',
          color: '#635BFF'
        },
        cards: {
          logo: '/icons/payment-providers/credit-cards.png',
          color: '#FFFFFF',
          height: 50
        },
        hoolah: {
          logo: '/icons/payment-providers/hoolah.png',
          color: '#D62E2E',
          height: 16 
        }
      }
    }
  },
  computed: {
    method () {
      return this.methods[this.value]
    }
  }
}
</script>

<style lang="scss" scoped>
.icon-container {
  width: 68px;
  height: 72px;

  .inner {
    width: 63px;
    height: 63px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, .2);
    border-radius: 6px;
  }
}
</style>
