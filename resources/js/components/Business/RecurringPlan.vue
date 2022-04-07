<template>
    <div>
        <div class="card-body bg-light border-top p-4">
            <div v-if="!recurring_plan.customer_id" class="form-group">
                <label for="search_customer" class="small text-muted text-uppercase">Search Customer</label>
                <input id="search_customer" class="form-control" title="" v-model="search_customer" :class="{
                    'is-invalid' : errors.customer_id
                }" placeholder="Enter customer email to search" @keyup="searchCustomer">
                <span class="invalid-feedback" role="alert">{{ errors.customer_id }}</span>
            </div>
            <div v-else class="form-group">
                <label for="name" class="small text-muted text-uppercase">Customer</label>
                <template v-if="customer.name">
                    <p class="mb-0">{{ customer.name }}</p>
                    <p class="small text-muted mb-0">{{ customer.email }}</p>
                </template>
                <template v-else>
                    <p class="mb-0">{{ customer.email }}</p>
                </template>
                <p v-if="customer.phone_number" class="small text-muted mb-0">{{ customer.phone_number }}</p>
                <p v-if="customer.address" class="small text-muted mb-0">{{ customer.address }}</p>
                <a href="#" class="small" @click.prevent="removeCustomer">Remove</a>
            </div>
            <div v-if="customers_result.length > 0" class="bg-white rounded border mb-3">
                <div v-for="(customer, index) in customers_result" class="p-3" :class="{
                    'border-top': index !== 0,
                }">
                    <button class="btn btn-sm btn-primary float-right" @click="addCustomer(customer)">Add</button>
                    <template v-if="customer.name">
                        {{ customer.name }}
                        <p class="small text-muted mb-0">Email: {{ customer.email }}</p>
                    </template>
                    <template v-else>
                        {{ customer.email }}
                    </template>
                    <p v-if="customer.phone_number" class="small text-muted mb-0">Phone Number: {{ customer.phone_number }}</p>
                    <p v-if="customer.address" class="small text-muted mb-0">Address: {{ customer.address }}</p>
                </div>
            </div>
            <div class="form-group">
                <label for="name" class="small text-muted text-uppercase">Plan Name</label>
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
                <label for="bank_swift_code" class="small text-muted text-uppercase">Select Renewal Cycle</label>
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
                <label class="small text-muted text-uppercase">Selling Price <span class="text-danger">*</span></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text" id="basic-addon3">{{ display_currency }}</span>
                    </div>
                    <input v-model="recurring_plan.price" :class="{ 'is-invalid' : errors.price }" class="form-control" placeholder="10.00" step="0.01" title="Selling Price">
                </div>
                <span v-if="errors.price" class="d-block small text-danger w-100 mt-1" role="alert">
                    {{ errors.price }}
                </span>
            </div>
            <div class="form-group">
                <label for="starts_at" class="small text-muted text-uppercase">Select Start Date</label>
                <datepicker id="starts_at" v-model="recurring_plan.starts_at_picker" :bootstrap-styling="true" placeholder="Click here to select date" :format="'yyyy-MM-dd'" :class="{
                    'border border-danger rounded' : errors.starts_at
                }"></datepicker>
                <span v-if="errors.starts_at" class="text-danger small" role="alert">{{ errors.starts_at }}</span>
            </div>
            <div class="form-group">
                <label class="small text-muted text-uppercase">Times To Be Charged (Optional)</label>
                <div class="input-group">
                    <input v-model="recurring_plan.times_to_be_charged" :class="{ 'is-invalid' : errors.times_to_be_charged }" class="form-control" title="Selling Price">
                </div>
                <span v-if="errors.times_to_be_charged" class="d-block small text-danger w-100 mt-1" role="alert">
                    {{ errors.times_to_be_charged }}
                </span>
            </div>
            <div class="form-group">
                <label for="send_email" class="text-muted">Send Email</label>
                <input type="checkbox" id="send_email" title="" v-model="recurring_plan.send_email"
                       :disabled="is_processing">
            </div>
            <button class="btn btn-primary" @click.prevent="saveMethod" :disabled="is_processing">
                <i class="fas fa-plus mr-3"></i> Save Recurring Plan
                <i v-if="is_processing" class="fas fa-spinner fa-spin"></i>
            </button>
        </div>
    </div>
</template>
<script>
import Datepicker from "vuejs-datepicker";

export default {
    components: {
        Datepicker,
    },
    data() {
        return {
            customer: {},
            customers_result: [],
            cycle_list: [],
            display_currency: 'SGD',
            errors: {},
            is_processing: false,
            recurring_plan: {
                id: null,
                customer_id: null,
                name: '',
                description: '',
                cycle: '',
                price: 0,
                starts_at_picker: '',
                starts_at: '',
                times_to_be_charged: null,
                send_email: true
            },
            search_customer: '',
            timeout: null,
        }
    },

    mounted() {
        this.cycle_list = Data.cycle;
        this.display_currency = Business.currency.toUpperCase();

        if (Template) {
            this.recurring_plan.name = Template.name;
            this.recurring_plan.description = Template.description;
            this.recurring_plan.cycle = Template.cycle;
            this.recurring_plan.price = Template.readable_price;
        }

        if (RecurringPlan){
            this.recurring_plan.id = RecurringPlan.id;
            this.recurring_plan.customer_id = RecurringPlan.business_customer_id ?? null;
            this.recurring_plan.name = RecurringPlan.name ?? '';
            this.recurring_plan.description = RecurringPlan.description ?? '';
            this.recurring_plan.cycle = RecurringPlan.cycle ?? '';
            this.recurring_plan.price = RecurringPlan.price ? (RecurringPlan.price / 100).toFixed(2) : 0;
            this.recurring_plan.send_email = RecurringPlan.send_email;
            this.recurring_plan.times_to_be_charged = RecurringPlan.times_charged;
            this.customer.address = RecurringPlan.customer_street ?? null;
            this.customer.email = RecurringPlan.customer_email ?? null;
            this.customer.id = RecurringPlan.business_customer_id ?? null;
            this.customer.name = RecurringPlan.customer_name ?? null;
            this.customer.phone_number = RecurringPlan.customer_phone_number ?? null;

        }
    },


    methods: {
        saveMethod() {
            this.is_processing = true;

            this.errors = {
                //
            };

            if (this.recurring_plan.starts_at_picker) {
                let date = (this.recurring_plan.starts_at_picker.getDate() + 1) + '';

                if (date.length === 1) {
                    date = '0' + date;
                }

                let month = (this.recurring_plan.starts_at_picker.getMonth() + 1) + '';

                if (month.length === 1) {
                    month = '0' + month;
                }

                this.recurring_plan.starts_at = date + '/' + month + '/' + this.recurring_plan.starts_at_picker.getFullYear();

            }
            if (this.recurring_plan.id !== null) {
                axios.put(this.getDomain('business/' + Business.id + '/recurring-plan/' + this.recurring_plan.id, 'dashboard'), this.recurring_plan).then(({data}) => {
                    window.location.href = data.redirect_url;
                }).catch(({response}) => {
                    this.recurring_plan.starts_at = '';

                    if (response.status === 422) {
                        _.forEach(response.data.errors, (value, key) => {
                            this.errors[key] = _.first(value);
                        });

                        this.showError(_.first(Object.keys(this.errors)));
                    }
                });
            } else {
                axios.post(this.getDomain('business/' + Business.id + '/recurring-plan', 'dashboard'), this.recurring_plan).then(({data}) => {
                    window.location.href = data.redirect_url;
                }).catch(({response}) => {
                    this.recurring_plan.starts_at = '';

                    if (response.status === 422) {
                        _.forEach(response.data.errors, (value, key) => {
                            this.errors[key] = _.first(value);
                        });

                        this.showError(_.first(Object.keys(this.errors)));
                    }
                });
            }
        },


        showError(firstErrorKey) {
            if (firstErrorKey !== undefined) {
                this.scrollTo('#' + firstErrorKey);

                $('#' + firstErrorKey).focus();
            }

            this.is_processing = false;
        },

        searchCustomer() {
            clearTimeout(this.timeout);

            this.timeout = setTimeout(() => {
                if (this.search_customer === '') {
                    this.customers_result = [];
                } else {
                    axios.post(this.getDomain('business/' + Business.id + '/point-of-sale/customer', 'dashboard'), {
                        keywords: this.search_customer,
                    }).then(({data}) => {
                        this.customers_result = data;
                    });
                }
            }, 500);
        },

        addCustomer(customer) {
            this.recurring_plan.customer_id = customer.id;
            this.customer = customer;
            this.search_customer = '';
            this.customers_result = [];
        },

        removeCustomer(id) {
            this.recurring_plan.customer_id = null;
        },
    },
}
</script>
