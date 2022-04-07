<template>
    <div>
        <div class="card-body bg-light border-top p-4">
            <div class="form-group">
                <label for="name" class="small text-muted text-uppercase">Name</label>
                <input id="name" class="form-control" title="" v-model="recurring_plan.name" :class="{
                    'is-invalid' : errors.name
                }" placeholder="Recurring Plan Name" :disabled="is_processing">
                <span class="invalid-feedback" role="alert">{{ errors.name }}</span>
            </div>
            <div class="form-group">
                <label for="description" class="small text-muted text-uppercase">Description</label>
                <textarea id="description" v-model="recurring_plan.description" class="form-control" rows="3" :class="{
                    'is-invalid' : errors.description
                }" placeholder="Details of this recurring plan…" :disabled="is_processing"></textarea>
                <span class="invalid-feedback" role="alert">{{ errors.description }}</span>
            </div>
            <div class="form-group">
                <label for="reference" class="small text-muted text-uppercase">Reference</label>
                <input id="reference" v-model="recurring_plan.reference" class="form-control" :class="{
                    'is-invalid' : errors.reference}" :disabled="is_processing">
                <span class="invalid-feedback" role="alert">{{ errors.reference }}</span>
            </div>
            <div class="form-group">
                <label for="bank_swift_code" class="small text-secondary">Select Renewal Cycle</label>
                <select id="bank_swift_code" class="custom-select" v-model="recurring_plan.cycle" :class="{
                    'is-invalid' : errors.cycle
                }" :disabled="is_processing">
                    <option value="" disabled>Select Renewal Cycle</option>
                    <option v-for="value in cycle_list" :value="value.value">
                        {{ value.name }}
                    </option>
                </select> <span class="invalid-feedback" role="alert">{{ errors.cycle }}</span>
            </div>
            <div class="form-group">
                <label>Selling Price <span class="text-danger">*</span></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text" id="basic-addon3">{{ display_currency }}</span>
                    </div>
                    <input v-model="recurring_plan.price" :class="{ 'is-invalid' : errors.price }" class="form-control" placeholder="0.00" step="0.01" title="Selling Price">
                </div>
                <span v-if="errors.price" class="d-block small text-danger w-100 mt-1" role="alert">
                    {{ errors.price }}
                </span>
            </div>
            <button class="btn btn-primary" @click.prevent="saveMethod" :disabled="is_processing">
                <template v-if="is_updating">
                    <i class="fas fa-save mr-3"></i> Save
                </template>
                <template v-else>
                    <i class="fas fa-plus mr-3"></i> Add Recurring Plan
                </template>
                <i v-if="is_processing" class="fas fa-spinner fa-spin"></i>
            </button>
        </div>
        <div v-if="is_updating" class="card-body border-top px-4 py-2">
            <a class="small text-danger" data-toggle="modal" data-target="#confirmationModal" href="#">Delete this recurring plan template</a>
            <div id="confirmationModal" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-body">
                            <h5 class="modal-title mb-3">Deleting Recurring Plan Template</h5>
                            <p>Are you sure you want to delete the recurring plan template "{{ original_name }}"?</p>
                            <p v-if="deleting_error" class="font-weight-bold text-danger">
                                <i class="fas fa-exclamation-circle mr-1"></i> {{ deleting_error }}</p>
                            <button type="button" class="btn btn-danger" @click.prevent="deleteMethod" :disabled="is_processing">
                                <i class="fas fa-times mr-1"></i> Delete
                                <i v-if="is_processing" class="fas fa-spinner fa-spin"></i>
                            </button>
                            <button type="button" class="btn btn-light" data-dismiss="modal" :disabled="is_processing">Close</button>
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
                cycle_list: [],
                deleting_error: null,
                display_currency: 'SGD',
                errors: {},
                is_processing: false,
                is_updating: false,
                original_name: '',
                recurring_plan: {
                    id: null,
                    name: '',
                    description: '',
                    reference: '',
                    cycle: '',
                    price: 0,
                },
            }
        },

        mounted() {
            if (RecurringPlan) {
                this.recurring_plan.id = RecurringPlan.id;
                this.recurring_plan.name = RecurringPlan.name;
                this.original_name = this.recurring_plan.name;
                this.recurring_plan.description = RecurringPlan.description;
                this.recurring_plan.reference = RecurringPlan.reference;
                this.recurring_plan.cycle = RecurringPlan.cycle;
                this.recurring_plan.price = RecurringPlan.readable_price;

                this.is_updating = true;
            } else {
                // this.recurring_plan.cycle = 'monthly';
            }

            this.cycle_list = Data.cycle;
            this.display_currency = Business.currency.toUpperCase();
        },


        methods: {
            saveMethod() {
                this.is_processing = true;

                this.errors = {
                    //
                };

                if (this.recurring_plan.id !== null) {
                    axios.put(this.getDomain('business/' + Business.id + '/recurring-plan/template/' + this.recurring_plan.id, 'dashboard'), this.recurring_plan).then(({data}) => {
                        window.location.href = data.redirect_url;
                    }).catch(({response}) => {
                        if (response.status === 422) {
                            _.forEach(response.data.errors, (value, key) => {
                                this.errors[key] = _.first(value);
                            });

                            this.showError(_.first(Object.keys(this.errors)));
                        }
                    });
                } else {
                    axios.post(this.getDomain('business/' + Business.id + '/recurring-plan/template', 'dashboard'), this.recurring_plan).then(({data}) => {
                        window.location.href = data.redirect_url;
                    }).catch(({response}) => {
                        if (response.status === 422) {
                            _.forEach(response.data.errors, (value, key) => {
                                this.errors[key] = _.first(value);
                            });

                            this.showError(_.first(Object.keys(this.errors)));
                        }
                    });
                }
            },

            deleteMethod() {
                this.is_processing = true;

                axios.delete(this.getDomain('business/' + Business.id + '/recurring-plan/template/' + this.recurring_plan.id, 'dashboard')).then(({data}) => {
                    window.location.href = data.redirect_url;
                }).catch(({response}) => {
                    if (response.status === 403) {
                        this.is_processing = false;
                        this.deleting_error = response.data.message;
                    }
                });
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
