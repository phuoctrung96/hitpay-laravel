<template>
    <div>
        <button class="btn btn-success mb-3" data-toggle="modal" data-target="#rulesModal">Add Rules</button>
        <div class="modal fade" id="rulesModal" tabindex="-1" role="dialog" aria-labelledby="refundExportModalLabel"
             aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="refundExportModalLabel">Add Rule</h5>
                        <button id="closeBtn" type="button" class="close" data-dismiss="modal" aria-label="Close"
                                :disabled="is_processing">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="business_id" class="small text-muted text-uppercase">Business Id</label>
                            <input id="business_id" type="text" class="form-control" title=""
                                   v-model="rule.business_id" :class="{
                        'is-invalid' : errors.business_id
                    }" :disabled="is_processing">
                            <span class="invalid-feedback" role="alert">{{ errors.business_id }}</span>
                        </div>
                        <div class="mb-3">
                            <label for="min_spend" class="small text-muted text-uppercase">Minimum charge amount</label>
                            <input id="min_spend" type="number" class="form-control" title=""
                                   v-model="rule.min_spend" :class="{
                        'is-invalid' : errors.min_spend
                    }" :disabled="is_processing">
                            <span class="invalid-feedback" role="alert">{{ errors.min_spend }}</span>
                        </div>
                        <div class="mb-3">
                            <label for="cashback_amt_fixed" class="small text-muted text-uppercase">Cashback fixed amount</label>
                            <input id="cashback_amt_fixed" type="number" class="form-control" title=""
                                   v-model="rule.cashback_amt_fixed" :class="{
                        'is-invalid' : errors.cashback_amt_fixed
                    }" :disabled="is_processing">
                            <span class="invalid-feedback" role="alert">{{ errors.cashback_amt_fixed }}</span>
                        </div>

                        <div class="mb-3">
                            <label for="cashback_amt_percent" class="small text-muted text-uppercase">Cashback percent amount</label>
                            <input id="cashback_amt_percent" type="number" class="form-control" title=""
                                   v-model="rule.cashback_amt_percent" :class="{
                        'is-invalid' : errors.cashback_amt_percent
                    }" :disabled="is_processing">
                            <span class="invalid-feedback" role="alert">{{ errors.cashback_amt_percent }}</span>
                        </div>

                        <div class="mb-3">
                            <label for="maximum_cap" class="small text-muted text-uppercase">Maximum cashback</label>
                            <input id="maximum_cap" type="number" class="form-control" title=""
                                   v-model="rule.maximum_cap" :class="{
                        'is-invalid' : errors.maximum_cap
                    }" :disabled="is_processing">
                            <span class="invalid-feedback" role="alert">{{ errors.maximum_cap }}</span>
                        </div>

                        <div class="mb-3">
                            <label for="total_cashback" class="small text-muted text-uppercase">Total cashback</label>
                            <input id="total_cashback" type="number" class="form-control" title=""
                                   v-model="rule.total_cashback" :class="{
                        'is-invalid' : errors.total_cashback
                    }" :disabled="is_processing">
                            <span class="invalid-feedback" role="alert">{{ errors.total_cashback }}</span>
                        </div>

                        <div class="text-right">
                            <button id="downloadBtn" type="button" class="btn btn-primary"
                                    @click.prevent="addRule" :disabled="is_processing">
                                Add Rule <i v-if="is_processing" class="fas fa-spinner fa-spin"></i>
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
    props:{
      campaign: Object,
    },
    data() {
        return {
            errors: {},
            modal: null,
            rule:{
                business_id: '',
                currency: 'sgd',
                min_spend: 0,
                cashback_amt_fixed: 0,
                cashback_amt_percent: 0,
                maximum_cap: 0,
                total_cashback: 0,
                balance_cashback: 0
            },
            is_succeeded: false,
            is_processing: false
        }
    },

    mounted() {
        this.modal = $('#rulesModal');
        this.modal.on('hidden.bs.modal', () => {
            this.is_processing = false;
            this.is_succeeded = false;
        });
    },


    methods: {
        addRule() {
            this.errors = {};
            this.is_processing = true;

            if (this.rule.cashback_amt_percent === 0 && this.rule.cashback_amt_fixed === '')
            {
                this.errors.cashback_amt_percent = 'Either percent or fixed value is required'
            }
            if (this.rule.cashback_amt_percent === '' && this.rule.cashback_amt_fixed === 0)
            {
                this.errors.cashback_amt_percent = 'Either percent or fixed value is required'
            }
            if (this.rule.cashback_amt_percent === 0 && this.rule.cashback_amt_fixed === 0)
            {
                this.errors.cashback_amt_percent = 'Either percent or fixed value is required'
            }
            if (this.rule.cashback_amt_percent === '' && this.rule.cashback_amt_fixed === '')
            {
                this.errors.cashback_amt_percent = 'Either percent or fixed value is required'
            }
            if (this.rule.min_spend === 0 || this.rule.min_spend === '' )
            {
                this.errors.min_spend = 'Minimum amount is required'
            }
            if (this.rule.maximum_cap === 0 || this.rule.maximum_cap === '')
            {
                this.errors.maximum_cap = 'Maximum cashback is required'
            }
            if (this.rule.total_cashback === '' || this.rule.total_cashback === 0)
            {
                this.errors.total_cashback = 'Total cashback is required'
            }
            if (this.rule.business_id === '')
            {
                this.errors.business_id = 'Business id is required'
            }

            if (Object.keys(this.errors).length > 0) {
                this.showError(_.first(Object.keys(this.errors)));
            }

            this.rule.balance_cashback = this.rule.total_cashback;

            this.is_processing = false;

            let addedRules = this.rule;
            this.rule = {
                currency: 'sgd'
            };

            $('#rulesModal').modal('hide');

            this.$emit('addRule', addedRules);
        },
        showError(firstErrorKey) {
            if (firstErrorKey !== undefined) {
                this.scrollTo('#' + firstErrorKey);

                $('#' + firstErrorKey).focus();
            }
            this.is_processing = false;
        },
    },
}
</script>
