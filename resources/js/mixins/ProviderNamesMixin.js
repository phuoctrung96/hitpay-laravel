export default {
  methods: {
    providerName (slug) {
      switch (slug) {
        case 'grabpay': {
          return 'GrabPay'
        }
        case 'shopee_pay': {
          return 'Shopee'
        }
        default: {
          return slug
        }
      }
    }
  }
}