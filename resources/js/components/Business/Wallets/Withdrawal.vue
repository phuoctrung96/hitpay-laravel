<template>
    <div>
        <div v-if="canSendBalanceToBank" class="mt-3">
            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#payoutModal">
                Pay To Bank Now
            </button>
        </div>
        <div v-else>
            <p class="mt-3"><i class="fa fa-exclamation-triangle fa-fw text-danger" /> Your role has no permission to send balance to bank.</p>
        </div>
        <div class="modal fade" id="payoutModal" tabindex="-1" aria-labelledby="payoutModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-body">
                        <h5 class="modal-title font-weight-bold mb-3" id="payoutModalLabel">Pay to bank now?</h5>
                        <p v-if="message" class="text-danger font-weight-bold"><i class="fas fa-exclamation-triangle"></i> {{ message }}</p>
                        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal" :disabled="is_processing">Close</button>
                        <button class="btn btn-sm btn-primary" @click="process" :disabled="is_processing">Yes <i v-if="is_processing" class="fas fa-spinner fa-spin"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "Withdrawal",
    props: {
        url: String,
        canSendBalanceToBank : {
            type : Boolean,
            default : false,
        },
    },
    data() {
        return {
            is_processing: false,
            message: null,
        }
    },
    methods: {
        process() {
            this.is_processing = true;

            axios.post(this.url).then(({data}) => {
                this.is_processing = false;

                location.reload();
            }).catch(({response}) => {
                console.log(response);
                if (response.status === 400) {
                    this.is_processing = false;
                    this.message = response.data.message
                }
            });
        },
    },
}
</script>
