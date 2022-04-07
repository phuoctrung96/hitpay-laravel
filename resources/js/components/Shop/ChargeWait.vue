<template>
  <div class="charge-wait h-100 d-flex flex-column align-items-center justify-content-center">
      <p class="mb-4"><img class="img-fluid" src="/icons/logo.png" alt="HitPay logo" width="300"></p>

      <p>Your payment is being processed…</p>
      <p class="text-uppercase font-weight-bold">Please do not refresh</p>
      <i class="fas fa-4x fa-spinner fa-spin"></i>
  </div>
</template>

<script>
export default {
  name: 'ChargeWait',
  props: {
    charge_id: String,
    business_id: String,
    timeout: {
      type: Number, // sec
      default: 6
    },
    interval: {
      type: Number,
      default: 1 // sec
    },
    backURL: {
      type: String,
      default: ''
    }
  },
  data () {
    return {
      timer: 0
    }
  },
  mounted () {
    this.checkCharge()
  },
  methods: {
    checkCharge () {
      window.setTimeout(async () => {
        try {
          const res = await axios.get(this.getDomain(`v1/business/${this.business_id}/plugin/charge/${this.charge_id}/charge-completed`, 'api'))

          if (res.data.completed) {
            // Redirect back
            this.redirectBack()
          } else {
            this.timer += this.interval

            if (this.timer < this.timeout) {
              this.checkCharge()
            } else {
              // Redirect back
              this.redirectBack()
            }
          }
        } catch (error) {
          // Do nothing
        }
      }, this.interval * 1000)
    },
    redirectBack () {
      let search = window.location.search + (window.location.search ? '&' : '?')
      search += 'noWait=true'

      if (this.backURL) {
        search += encodeURIComponent('&backURL=' + this.backURL)
      }

      window.location.href = window.location.protocol + '//' + window.location.host + window.location.pathname + search
    }
  }
}
</script>

<style lang="scss">
.charge-wait {

}
</style>