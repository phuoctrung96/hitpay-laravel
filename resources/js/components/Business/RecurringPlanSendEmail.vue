<template>
    <div class="d-inline">
        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#sendLinkModal">Send Link</button>
        <div id="sendLinkModal" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-body">
                        <template v-if="is_succeeded">
                            <h5 class="modal-title mb-4">Link Sent</h5>
                            <p>The link has been successfully sent.</p>
                            <div class="text-right">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </template>
                        <template v-else>
                            <h5 class="modal-title mb-4">Send Link</h5>
                            <p>An email will be sent to {{ recipient }}.</p>
                            <div class="text-right">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal" :disabled="is_processing">Close</button>
                                <button type="button" class="btn btn-primary" @click="sendLink" :disabled="is_processing">
                                    Confirm <i class="fas fa-spinner fa-spin" v-if="is_processing"></i>
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
                recurring_plan: null,
                is_processing: false,
                is_succeeded: false,
                modal: null,
                recipient: ''
            }
        },

        mounted() {
            this.business = Business;
            this.recurring_plan = RecurringPlan;

            this.modal = $('#sendLinkModal');
            this.modal.on('hidden.bs.modal', () => {
                this.is_processing = false;
                this.is_succeeded = false;
            });

            this.recipient = this.recurring_plan.customer_email;
        },


        methods: {
            async sendLink() {
                this.is_processing = true;

                await axios.post(this.getDomain('business/' + this.business.id + '/recurring-plan/' + this.recurring_plan.id + '/send', 'dashboard')).then(({data}) => {
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
