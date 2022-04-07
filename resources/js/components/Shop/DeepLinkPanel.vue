<template>
  <transition name="deeplink-slide">
    <div
      v-if="value"
      class="deep-link-panel"
      @click.self="$emit('input', false)"
      @touchmove.prevent>

      <div
        class="container"
        ref="container"
        :style="containerStyle"
        @touchstart="onTouchStart"
        @touchmove.stop.prevent="onTouchMove">

        <div class="title-container d-flex">
          <span class="title flex-grow-1 text-center">Select Your Banking App</span>

          <img
            src="/icons/cross.svg"
            height="21"
            @click="$emit('input', false)"/>
        </div>

        <div class="banks">
          <div
            v-for="(bank, index) in banks"
            :key="index"
            class="bank-item"
            @click="onClick(bank)">

            <div class="logo-container">
              <template v-if="bank.cashback">
                <img
                  class="cashback"
                  src="/images/cashback.svg"/>

                <div class="cashback d-flex flex-column justify-content-center align-items-center">
                  <span class="amount">{{ bank.cashback }}</span>
                  <span class="text">cashback</span>
                </div>
              </template>

              <img
                :src="bank.icon"
                class="logo"
                width="75"
                height="75"/>
            </div>

            <span>{{ bank.name }}</span>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<script>
export default {
  name: 'DeepLinkPanel',
  props: {
    value: {
      type: Boolean,
      default: false
    },
    transactionId: String,
    qrString: String,
    cashbackFor: {
      type: String,
      default: ''
    },
    cashbackAmount: {
      type: String,
      default: ''
    },
    payNowBank: {
      type: String
    }
  },
  data () {
    return {
      active: false,
      banks: [
        {
          name: 'DBS / POSB',
          slug: 'dbs',
          icon: '/icons/payment-banks/banks-dbs-2.png'
        },
        {
          name: 'PayLah!',
          slug: 'paylah',
          icon: '/icons/payment-banks/banks-dbs-paylah-2.png'
        },
        {
          name: 'OCBC',
          slug: 'ocbc',
          icon: '/icons/payment-banks/banks-ocbc-2.png'
        },
        {
          name: 'Google Pay',
          slug: 'gpay',
          icon: '/icons/payment-banks/banks-googlepay-2.png',
        },
        {
          name: 'UOB',
          slug: 'uob',
          icon: '/icons/payment-banks/banks-uob-2.png'
        },
        {
          name: 'Citibank',
          slug: 'citi',
          icon: '/icons/payment-banks/banks-citi-2.png'
        },
        {
          name: 'HSBC',
          slug: 'hsbc',
          icon: '/icons/payment-banks/banks-hsbc-2.png'
        },
        {
          name: 'SC',
          slug: 'sc',
          icon: '/icons/payment-banks/banks-sc-2.png'
        },
        {
          name: 'Maybank',
          slug: 'maybank',
          icon: '/icons/payment-banks/banks-maybank-2.png'
        },
        {
          name: 'Bank of China',
          slug: 'maybank',
          icon: '/icons/payment-banks/banks-china-2.png'
        },
      ],
      visible: false,
      touchY: 0,
      touchHeight: 0,
      heightStart: 370,
      heightEnd: 580
    }
  },
  computed: {
    containerStyle () {
      let res = {}

      if (this.value && this.touchHeight > 0) {
        res['max-height'] = this.touchHeight + 'px'
      }

      return res
    },
  },
  watch: {
    // Reset touchHeight value
    value (value) {
      if (!value) {
        this.touchHeight = 0
      }
    }
  },
  mounted () {
    this.$nextTick(() => {
      this.visible = true
    })

    // Process cashback info
    if (this.cashbackFor && this.cashbackAmount) {
      const banks = this.cashbackFor.split(',')

      let amounts = this.cashbackAmount.split(',')

      if (amounts.length < banks.length) {
        amounts = new Array(banks.length).fill(amounts[0])
      }

      banks.forEach((slug, slugIndex) => {
        const index = this.banks.findIndex(bank => bank.slug === slug)

        if (index >= 0) {
          this.$set(this.banks[index], 'cashback', amounts[slugIndex])
        }
      })
    }
  },
  methods: {
    onClick (bank) {
      switch (bank.slug) {
        case 'ocbc': {
          // TBD
          const deepLink = `ocbcpao://readQR?version=3&appID=&intentAction=&shouldOpenInExternalBrowser=true&qrString=${this.qrString}&returnURI=${encodeURIComponent(location.href)}&transactionID=${this.transactionId}`
          this.$emit('redirect', deepLink)
          break
        }
        default: {
          this.$emit('update:payNowBank', bank.slug)
          this.$emit('input', false)
          break
        }
      }
    },
    onTouchStart (event) {
      this.touchY = event.changedTouches[0].screenY
    },
    onTouchMove (event) {
      if (this.touchHeight <= 0) {
       this.touchHeight = this.heightStart;
      }

      const diff = this.touchY - event.changedTouches[0].screenY
      this.touchY = event.changedTouches[0].screenY

      if (this.touchHeight + diff <= this.heightEnd) {
        this.touchHeight += diff
      }

      if (this.touchHeight < 120) {
        this.$emit('input', false)
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.deep-link-panel {
  position: fixed;
  bottom: 0;
  top: 0;
  left: 0;
  right: 0;
  backdrop-filter: blur(6px);
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  z-index: 3;

  .container {
    background-color: white;
    border-top-left-radius: 24px;
    border-top-right-radius: 24px;
    display: flex;
    flex-direction: column;
    box-shadow: 0px 0 10px rgba(0, 0, 0, 0.3);
    max-height: 370px;

    .title-container {
      margin-top: 32px;
      margin-bottom: 32px;

      span.title {
        margin-left: 12px;
        font-size: 16px;
        font-weight: 500;
        color: #4A4A4A;
      }

      img {
        margin-right: 12px;
        cursor: pointer;
      }
    }

    .banks {
      display: flex;
      flex-wrap: wrap;

      .bank-item {
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        align-items: center;
        flex: 0 0 33%;

        .logo-container {
          position: relative;

          img.logo {
            border-radius: 13px;
            box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.5);
            margin-bottom: 8px;
          }

          img.cashback {
            position: absolute;
            z-index: 2;
            right: -12px;
            top: -12px;
          }

          div.cashback {
            position: absolute;
            z-index: 3;
            right: -12px;
            top: -12px;
            width: 48px;
            height: 48px;

            span {
              margin-bottom: 0;
              color: white;
              line-height: 1.2;
            }

            .amount {
              font-size: 8px;
              font-weight: 500;
            }

            .text {
              font-size: 8px;
            }
          }
        }

        span {
          margin-bottom: 16px;
          color: #4A4A4A;
          font-size: 16px;
          font-weight: 500; // medium
        }
      }
    }
  }
}

// Combined transition, fade + slide
.deeplink-slide-leave-active,
.deeplink-slide-enter-active {
  transition: opacity .5s ease;

  .container {
    transition: max-height .5s ease-out;
  }
}

.deeplink-slide-enter-to, .deeplink-slide-leave {
  .container {
    max-height: 370px;
    overflow: hidden;
  }
}

.deeplink-slide-enter,
.deeplink-slide-leave-to {
  opacity: 0;

  .container {
    overflow: hidden;
    max-height: 0;
  }
}
</style>
