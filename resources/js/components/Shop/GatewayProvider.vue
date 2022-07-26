<template>
    <div class="accepted-payment" id="accepted-payment">
        <span v-for="(gatewayProvider, index) in gateway_providers" :key="index">
            <img :src="gatewayProviderImages[gatewayProvider].img" :width="gatewayProviderImages[gatewayProvider].width" style="margin-right: 4px;">
        </span>
    </div>
</template>

<script>

export default {
    data() {
        return {
            gatewayProviderImages : {
              paynow_online: {
                img: '/icons/payment-providers/paynow.png',
                width: "55px",
              },
              visa: {
                img: '/icons/payment-brands/visa.png',
                width: "55px",
              },
              master: {
                img: '/icons/payment-brands/master.png',
                width: "55px",
              },
              amex: {
                img: '/icons/payment-brands/amex.svg',
                width: "55px",
              },
              wechat: {
                img: '/icons/payment-methods/weechat-new2.svg',
                width: "55px",
              },
              alipay: {
                img: '/icons/payment-methods/alipay-new.svg',
                width: "",
              },
              apple: {
                img: '/icons/payment-brands/apple-pay.svg',
                width: "55px",
              },
              grabpay: {
                img: '/icons/payment-methods/grabpay2.png',
                width: "55px",
              },
              grab_paylater: {
                img: '/icons/payment-brands/grabpay_paylater.svg',
                width: "",
              },
              grabpay_direct: {
                img: '/icons/payment-methods/grabpay3.png',
                width: "55px",
              },
              grabpay_paylater: {
                img: '/icons/payment-methods/grabpay_paylater2.png',
                width: "150px",
              },
              unionpay: {
                img: '/icons/payment-brands/unionpay.svg',
                width: "55px",
              },
              googlepay: {
                img: '/icons/payment-brands/gpay.svg',
                width: "60px",
              },
              zip: {
                img: '/icons/payment-brands/zip.svg',
                width: "55px",
              },
              shopee_pay: {
                img: '/icons/payment-brands/shopee.svg',
                width: "55px",
              },
              fpx: {
                img: '/icons/payment-brands/fpx.png',
                width: "55px",
              }
            },
            gateway_providers: null
        };
    },

    mounted() {
        this.getGatewayProvider();
    },

    methods: {
        getGatewayProvider() {
            const businessId = window.BusinessId;

            axios.get(this.getDomain(`${businessId}/gateway-provider-store`, 'shop')).then(response => {
                if (response.data.items.length > 0) {
                    this.gateway_providers = response.data.items;
                }
            });
        }
    },
}
</script>
