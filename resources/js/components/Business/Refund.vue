<template>
    <div class="d-inline">
        <button
          class="btn btn-warning"
          @click="onRefundClick">
          {{ button_text }}
        </button>

        <div id="refundModal" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-body">
                        <h5 class="modal-title text-danger font-weight-bold mb-3">
                            Are you sure you want to {{ action_text }}?
                        </h5>

                        <template v-if="partialRefundSupport">
                          <p class="text-muted">Enter an amount if you want to {{ action_text }} partially.</p>
                          <div class="mb-3">
                              <div class="input-group">
                                  <div class="input-group-prepend">
                                      <span class="input-group-text text-uppercase">{{ currency }}</span>
                                  </div>
                                  <input v-model="amount" type="number" class="form-control bg-light" :class="{
                                      'is-invalid': error,
                                  }" title="Amount" :disabled="is_processing" @keypress="isNumber($event)">
                              </div>
                              <span v-if="campaignCashback" class="small d-block">For this transaction the customer has enjoyed a ${{ (campaignCashback.amount/100).toFixed(2)}} cashback, the maximum refund amount is ${{ (maxRefundAmount/100).toFixed(2)}}</span>
                              <span v-if="error" class="text-danger" role="alert"><small>{{ error }}</small></span>
                          </div>
                          <a href="https://hitpay.zendesk.com/hc/en-us/articles/900004328183-What-are-the-charges-for-refunds-and-chargebacks-" target="_blank">What are the charges for refunds?</a>
                        </template>

                        <div class="text-right">
                            <button type="button" class="btn btn-danger" @click="refund" :disabled="is_processing">
                                Confirm <i class="fas fa-spinner fa-spin" v-if="is_processing"></i>
                            </button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal" :disabled="is_processing">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="cantRefundModal" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-body">
                        <h5 class="modal-title text-danger font-weight-bold mb-3">
                            Error
                        </h5>

                        <div class="mb-3">
                          Refund cannot be done on this transaction until it’s available. Please retry tomorrow.
                        </div>

                        <div class="text-right">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import isNumber from '../../mixins/NumberValidationMixin';
    export default {
        mixins: [isNumber],
        data() {
            return {
                action_text: 'refund',
                business: null,
                button_text: 'Refund',
                charge: null,
                error: null,
                is_processing: false,
                is_succeeded: false,
                modal: null,
                currency: 'SGD',
                amount: null,
            }
        },

        mounted() {
            this.business = Business;
            this.charge = Charge;

            //this.amount = this.charge.amount

            if (this.charge.payment_provider === 'hitpay') {
                this.action_text = 'void';
                this.button_text = 'Void';
            }

            this.currency = this.charge.currency;

            this.modal = $('#refundModal');
            this.modal.on('hidden.bs.modal', () => {
                this.is_succeeded = false;
                this.error = null;
            });
        },

        computed: {
          partialRefundSupport () {
            if (this.charge) {
              switch (this.charge.payment_provider) {
                case 'shopee_pay': {
                  return false
                }
                default: {
                  return true
                }
              }
            } else {
              return false
            }
          },
            maxRefundAmount(){
                return this.charge.amount - this.campaignCashback.amount;
            },
            campaignCashback(){
                if (this.charge)
                    return (this.charge.refunds).find(x => x.is_campaign_cashback === 1);
                return null;
            }
        },

        methods: {
            async onRefundClick () {
              // Check if charge can be refunded
              const { data } = await axios.get(this.getDomain('business/' + this.business.id + '/charge/' + this.charge.id + '/canrefund', 'dashboard'))

              if (data.canRefund) {
                $("#refundModal").modal();
              } else {
                $("#cantRefundModal").modal();
              }
            },

            async refund() {
                this.is_processing = true;
                this.error = null;

                if (this.partialRefundSupport && this.amount < 0.5) {
                    this.error = 'Amount must be at least 0.5';
                    this.is_processing = false;
                    return;
                }

                if (this.campaignCashback) {
                    if (parseFloat(this.amount) > this.maxRefundAmount/100) {
                        this.error = "Maximum refund amount is " + (this.maxRefundAmount / 100).toFixed(2);
                        this.is_processing = false;
                        return;
                    }
                }

                try {
                  let endpoint = 'business/' + this.business.id + '/charge/' + this.charge.id

                  switch (this.charge.payment_provider) {
                    case 'grabpay':
                    case 'shopee_pay':
                    case 'zip': {                      
                      endpoint += '/wallet/refund'
                      break
                    }
                    default: {
                      endpoint += '/refund'
                      break
                    }
                  }

                  let data = {}

                  if (this.partialRefundSupport) {
                    data.amount = this.amount
                  }

                  const res = await axios.post(this.getDomain(endpoint, 'dashboard'), data, {
                    withCredentials: true
                  })

                  window.location = res.data.redirect_url
                } catch (error) {
                  this.is_processing = false

                  if (error.response.data.errors && error.response.data.errors.amount) {
                      this.error = _.first(error.response.data.errors.amount)
                  } else {
                      this.error = error.response.data.message
                  }
                }

                /*
                await axios.post(this.getDomain('business/' + this.business.id + '/charge/' + this.charge.id + '/refund', 'dashboard'), {
                    amount: this.amount,
                }, {
                    withCredentials: true,
                }).then(({data}) => {
                    window.location = data.redirect_url;
                }).catch(({response}) => {
                    this.is_processing = false;

                    if (response.data.errors && response.data.errors.amount) {
                        this.error = _.first(response.data.errors.amount);
                    } else {
                        this.error = response.data.message;
                    }
                });
                */
            },
        },
    }
</script>
