<template>
    <div class="d-inline">
        <button class="btn btn-primary" data-toggle="modal" data-target="#sendReceiptModal">Send Receipt</button>
        <div id="sendReceiptModal" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-body">
                        <template v-if="is_succeeded">
                            <h5 class="modal-title mb-4">Receipt Sent</h5>
                            <p>The receipt has been successfully sent to
                                <span class="font-weight-bold">{{ recipient }}</span>.</p>
                            <div class="text-right">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </template>
                        <template v-else>
                            <h5 class="modal-title mb-4">Send Receipt</h5>
                            <p>Enter the recipient email addresss:</p>
                            <div class="mb-3">
                                <input v-model="recipient" class="form-control bg-light" :class="{
                                    'is-invalid': error,
                                }" title="Recipient Email" placeholder="Recipient Email" :disabled="is_processing">
                                <span class="invalid-feedback" role="alert">{{ error }}</span>
                            </div>
                            <div class="text-right">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal" :disabled="is_processing">Close</button>
                                <button type="button" class="btn btn-primary" @click="sendReceipt" :disabled="is_processing">
                                    Send <i class="fas fa-spinner fa-spin" v-if="is_processing"></i>
                                </button>
                            </div>
                        </template>
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
                error: null,
                is_processing: false,
                is_succeeded: false,
                modal: null,
                recipient: ''
            }
        },

        mounted() {
            this.business = Business;
            this.charge = Charge;

            this.modal = $('#sendReceiptModal');
            this.modal.on('hidden.bs.modal', () => {
                this.is_succeeded = false;
                this.recipient = '';
                this.error = null;
            });

            this.recipient = this.charge.customer_email;
        },


        methods: {
            async sendReceipt() {
                if (!this.recipient) {
                    return this.error = 'The recipient email can\'t be empty.'
                } else if (!this.validateEmail(this.recipient)) {
                    return this.error = 'The entered email is invalid.'
                }

                this.is_processing = true;
                this.error = null;

                await axios.post(this.getDomain('business/' + this.business.id + '/charge/' + this.charge.id + '/receipt', 'dashboard'), {
                    email: this.recipient,
                }).then(({data}) => {
                    this.is_processing = false;
                    this.is_succeeded = true;
                }).catch(({response}) => {
                    this.is_processing = false;
                    this.error = response.data.message;
                });
            },
        },
    }
</script>
