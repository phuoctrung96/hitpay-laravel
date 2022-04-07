<template>
    <div>
        <div class="card shadow-sm mb-3">
            <div class="card-body border-top p-4">
                <p class="text-uppercase text-muted">Role Restrctions</p>
                <div class="form-row">
                    <div class="col-12 col-md-6 mb-3">
                        <p class="text-uppercase mb-2">Cashier</p>
                        <div class="custom-control custom-switch">
                            <input id="switch-cashier-refund" v-model="role_restrictions['cashier@refund']" type="checkbox"
                                   class="custom-control-input"
                                   :disabled="is_processing">
                            <label v-if="role_restrictions['cashier@refund']" for="switch-cashier-refund" class="custom-control-label">Refund is
                                enabled</label>
                            <label v-if="!role_restrictions['cashier@refund']" for="switch-cashier-refund" class="custom-control-label">Refund is
                                disabled</label>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12">
                        <button class="btn btn-primary" @click="updateRestriction"
                                :disabled="is_processing_restriction || is_restriction_succeeded">
                            <i class="fas fa-save mr-1"></i> Update
                            <i v-if="is_processing_restriction" class="fas fa-spinner fa-spin"></i>
                        </button>
                        <p v-if="is_restriction_succeeded" class="text-success font-weight-bold mb-0 mt-3">
                            <i class="fas fa-check-circle mr-2"></i> Updated successfully!</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import 'vue2-timepicker/dist/VueTimepicker.css';

import VueTimepicker from 'vue2-timepicker';

export default {

    data() {
        return {
            business: {
                country_name: null,
                street: null,
                city: null,
                state: null,
                postal_code: null,
                can_pick_up: false,
                phone_number: null,
                currency_name: null,
                identifier: null,
                name: null,
                email: null,
                display_name: null,
                statement_description: null,
                slots: [],
                introduction: null,
                seller_notes: null,
            },

            role_restrictions: {
                'cashier@refund': true,
            },
            errors: {
                //
            },
            is_processing: false,
            is_processing_restriction: false,
            is_restriction_succeeded: false,
        };
    },

    mounted() {
        this.business = Business;

        _.each(Restrictions, (value) => {
            if (this.role_restrictions[value.role + '@' + value.restriction] !== undefined) {
                this.role_restrictions[value.role + '@' + value.restriction] = false;
            }
        });
    },

    methods: {
        updateRestriction() {
            this.is_processing_restriction = true;
            this.errors = {};

            axios.put(this.getDomain('business/' + this.business.id + '/role-restrictions', 'dashboard'), this.role_restrictions).then(({data}) => {
                this.is_processing_restriction = false;
                this.is_restriction_succeeded = true;

                setTimeout(() => {
                    this.is_restriction_succeeded = false;
                }, 5000);
            });
        },

        showError(firstErrorKey) {
            if (firstErrorKey !== undefined) {
                this.scrollTo('#' + firstErrorKey);
            }

            this.is_processing = false;
        },
    }
}
</script>
