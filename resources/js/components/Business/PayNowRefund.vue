<style scoped>
    #paynow_online-qr-code img {
        max-width: 100%;
        height: auto;
        margin-left: auto;
        margin-right: auto;
    }
</style>

<template>
    <div class="d-inline">
        <button class="btn btn-warning" data-toggle="modal" data-target="#refundModal">Refund</button>
        <div id="refundModal" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div v-if="step === 'confirmation'" class="modal-body">
                        <h5 class="modal-title text-danger font-weight-bold mb-3">
                            Are you sure you want to refund?
                        </h5>
                        <div class="mb-3">
                            <label class="small text-uppercase">Customer Name</label>
                            <input v-model="sender_name" class="form-control bg-light" title="Sender Name" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="small text-uppercase">Enter refund amount</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text text-uppercase">{{ currency }}</span>
                                </div>
                                <input v-model="refunding_amount" type="number" class="form-control bg-light" :class="{
                                    'is-invalid': error.amount,
                                }" title="Amount" :disabled="is_processing">
                            </div>
                            <span v-if="campaign_cashback" class="small d-block">For this transaction the customer has enjoyed a ${{ (campaign_cashback.amount/100).toFixed(2)}} cashback, the maximum refund amount is ${{ (maxRefundAmount/100).toFixed(2)}}</span>
                            <span v-if="error.amount" class="text-danger" role="alert"><small v-html="error.amount"></small></span>
                        </div>
                        <div class="text-right">
                            <button type="button" class="btn btn-danger" @click="refund" :disabled="is_processing">
                                Confirm <i class="fas fa-spinner fa-spin" v-if="is_processing"></i>
                            </button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal" :disabled="is_processing">Cancel</button>
                        </div>
                    </div>
                    <div v-if="step === 'waiting_for_payment'" class="modal-body bg-light text-center">
                        <h3 class="mb-3">Refunding...</h3>
                        <i class="fas fa-spinner fa-spin fa-3x mb-3"></i>
                        <p>{{ currency }}{{ Number(refunding_amount).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,') }}</p>
                        <div class="mb-3">
                            <div id="paynow_online-qr-code"></div>
                        </div>
                        <p>Awaiting payment for refund. Please do not close this window until you receive refund confirmation.</p>
                    </div>
                    <div v-if="step === 'refunded'" class="modal-body bg-light text-center">
                        <h3 class="mb-3">Done!</h3>
                        <p><i class="fas fa-check-circle fa-3x text-success"></i></p>
                        <p>Refund successful.</p>
                        <p class="h3 mb-3">{{ currency }}{{ Number(refunding_amount).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,') }}</p>
                        <a class="btn btn-primary" href="#" onclick="event.preventDefault(); location.reload()">Back to Dashboard</a>
                    </div>
                    <div v-if="step === 'failed'" class="modal-body bg-light text-center">
                        <h3 class="mb-3">Failed!</h3>
                        <p><i class="fas fa-times-circle fa-3x text-danger"></i></p>
                        <p>Refund failed. Please try again later</p>
                        <p class="h3 mb-3">{{ currency }}{{ Number(refunding_amount).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,') }}</p>
                        <a class="btn btn-primary" href="#" onclick="event.preventDefault(); location.reload()">Back to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    export default {
        data() {
            return {
                business: null,
                charge: null,
                currency: 'SGD',
                error: {
                    //
                },
                is_processing: false,
                modal: null,
                phone_number: null,
                phone_number_confirmation: null,
                sender_name: 'Unknown',
                refunding_amount: null,
                refund_id: null,
                step: 'confirmation',
            }
        },

        mounted() {
            this.business = Business;
            this.charge = Charge;

            this.currency = this.charge.currency.toUpperCase()
            this.amount = this.charge.amount;

            if (this.charge.data && this.charge.data.txnInfo && this.charge.data.txnInfo.senderParty && this.charge.data.txnInfo.senderParty.name) {
                this.sender_name = this.charge.data.txnInfo.senderParty.name;
            }

            this.modal = $('#refundModal');
            this.modal.on('hidden.bs.modal', () => {
                this.step = 'confirmation';
                this.is_processing = false;
                this.error = {
                    //
                };
            });
        },


        methods: {
            async refund() {
                this.is_processing = true;
                this.error = {
                    //
                };

                if (this.campaign_cashback) {
                    if (parseFloat(this.refunding_amount) > this.maxRefundAmount/100) {
                        this.error.amount = "Maximum refund amount is " + (this.maxRefundAmount / 100).toFixed(2);
                        this.is_processing = false;
                        return;
                    }
                }

                this.step = 'waiting_for_payment';

                await axios.post(this.getDomain('business/' + this.business.id + '/charge/' + this.charge.id + '/paynow/refund', 'dashboard'), {
                    amount: this.refunding_amount,
                }, {
                    withCredentials: true,
                }).then(({data}) => {
                    this.refunding_amount = data.amount;
                    this.refund_id = data.id;

                    this.pollChargeStatus();
                }).catch(({response}) => {
                    this.step = 'confirmation';
                    this.is_processing = false;

                    _.forEach(response.data.errors, (value, key) => {
                        this.error[key] = _.first(value);
                    });
                });
            },

            async pollChargeStatus(start = 0) {
                start++;

                await axios.post(this.getDomain('business/' + this.business.id
                    + '/charge/' + this.charge.id
                    + '/paynow/refund/' + this.refund_id, 'dashboard')).then(({data}) => {
                    if (data.status === 'succeeded') {
                        this.step = 'refunded';
                    } else if (data.status === 'failed') {
                        this.step = 'failed';
                    } else if (start <= 150) {
                        setTimeout(this.pollChargeStatus, 2000, start);
                    } else {
                        console.warn(new Error('Polling timed out.'));
                    }
                });
            },
        },
        computed:{
            maxRefundAmount(){
                return this.charge.amount - this.campaign_cashback.amount;
            },
            campaign_cashback(){
                if (this.charge)
                    return (this.charge.refunds).find(x => x.is_campaign_cashback === 1);
                return null;
            }
        }
    }
</script>
