<template>
    <div>
        <button class="btn btn-primary" data-toggle="modal" data-target="#exportModal">Export Customers</button>
        <div class="modal fade" id="exportModal" tabindex="-1" role="dialog" aria-labelledby="exportModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exportModalLabel">Export</h5>
                        <button id="closeBtn" type="button" class="close" data-dismiss="modal" aria-label="Close" :disabled="is_requesting">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div v-if="is_succeeded" class="modal-body">
                        The CSV will be sent to your email shortly.
                    </div>
                    <div v-else class="modal-body">
                        <p v-if="error" class="text-danger">{{ error }}</p>
                        <div class="text-right">
                            <button id="downloadBtn" type="button" class="btn btn-primary" @click.prevent="requestReport" :disabled="is_requesting">
                                Download <i v-if="is_requesting" class="fas fa-spinner fa-spin"></i>
                            </button>
                        </div>
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
            error: null,
            modal: null,
            is_requesting: false,
            is_succeeded: false,
        }
    },

    mounted() {
        this.modal = $('#exportModal');
        this.modal.on('hidden.bs.modal', () => {
            this.is_requesting = false;
            this.is_succeeded = false;
        });
    },


    methods: {
        requestReport() {
            this.is_requesting = true;

            axios.post(this.getDomain('business/' + Business.id + '/customer/export', 'dashboard')).then(({data}) => {
                this.is_requesting = false;
                this.is_succeeded = true;
            }).catch(({response}) => {
                if (response.status === 422) {
                    this.is_requesting = false;
                    this.error = response.data.message;
                }
            });
        },
    },
}
</script>
