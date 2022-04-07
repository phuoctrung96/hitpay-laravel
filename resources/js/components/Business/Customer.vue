<template>
    <div>
        <div class="card-body bg-light border-top p-4">
            <div class="form-group">
                <label for="name" class="small text-muted text-uppercase">Name</label>
                <input id="name" class="form-control" title="" v-model="customer.name" :class="{
                    'is-invalid' : errors.name
                }" placeholder="Customer Name" :disabled="is_processing">
                <span class="invalid-feedback" role="alert">{{ errors.name }}</span>
            </div>
            <div class="form-row">
                <div class="col-12 col-sm-8 col-lg-6 mb-3">
                    <label for="email" class="small text-muted text-uppercase">Email</label>
                    <input id="email" class="form-control" title="" v-model="customer.email" :class="{
                        'is-invalid' : errors.email
                    }" placeholder="Customer Email" :disabled="is_processing">
                    <span class="invalid-feedback" role="alert">{{ errors.email }}</span>
                </div>
                <div class="col-12 col-sm-8 col-lg-6 mb-3">
                    <label for="phone_number" class="small text-muted text-uppercase">Phone Number</label>
                    <input id="phone_number" class="form-control" title="" v-model="customer.phone_number" :class="{
                        'is-invalid' : errors.phone_number
                    }" placeholder="Customer Phone Number" :disabled="is_processing">
                    <span class="invalid-feedback" role="alert">{{ errors.phone_number }}</span>
                </div>
            </div>
            <div class="form-group">
                <label for="street" class="small text-muted text-uppercase">Street</label>
                <input id="street" class="form-control" title="" v-model="customer.street" :class="{
                    'is-invalid' : errors.street
                }" placeholder="Customer Address Street" :disabled="is_processing">
                <span class="invalid-feedback" role="alert">{{ errors.street }}</span>
            </div>
            <div class="form-row">
                <div class="col-12 col-sm-8 col-lg-6 mb-3">
                    <label for="city" class="small text-muted text-uppercase">City</label>
                    <input id="city" class="form-control" title="" v-model="customer.city" :class="{
                        'is-invalid' : errors.city
                    }" placeholder="Customer City" :disabled="is_processing">
                    <span class="invalid-feedback" role="alert">{{ errors.city }}</span>
                </div>
                <div class="col-12 col-sm-8 col-lg-6 mb-3">
                    <label for="state" class="small text-muted text-uppercase">State</label>
                    <input id="state" class="form-control" title="" v-model="customer.state" :class="{
                        'is-invalid' : errors.state
                    }" placeholder="Customer State" :disabled="is_processing">
                    <span class="invalid-feedback" role="alert">{{ errors.state }}</span>
                </div>
            </div>
            <div class="form-row">
                <div class="col-12 col-sm-8 col-lg-6 mb-3">
                    <label for="country" class="small text-muted text-uppercase">Country</label>
                    <select id="country" class="custom-select" v-model="customer.country" :class="{
                        'is-invalid' : errors.country
                    }" :disabled="is_processing">
                        <option v-for="country in countries" :value="country.code">
                            {{ country.name }}
                        </option>
                    </select> <span class="invalid-feedback" role="alert">{{ errors.country }}</span>
                </div>
                <div class="col-12 col-sm-8 col-lg-6 mb-3">
                    <label for="postal_code" class="small text-muted text-uppercase">Postal Code</label>
                    <input id="postal_code" class="form-control" title="" v-model="customer.postal_code" :class="{
                        'is-invalid' : errors.postal_code
                    }" placeholder="Customer Postal Code" :disabled="is_processing">
                    <span class="invalid-feedback" role="alert">{{ errors.postal_code }}</span>
                </div>
            </div>
            <div class="form-group">
                <label for="description" class="small text-muted text-uppercase">Remark</label>
                <textarea id="description" v-model="customer.remark" class="form-control" rows="3" :class="{
                    'is-invalid' : errors.remark
                }" placeholder="Tell more about this customer…" :disabled="is_processing"></textarea>
                <span class="invalid-feedback" role="alert">{{ errors.remark }}</span>
            </div>
            <button class="btn btn-primary" @click.prevent="saveMethod" :disabled="is_processing">
                <template v-if="is_updating">
                    <i class="fas fa-save mr-3"></i> Save
                </template>
                <template v-else>
                    <i class="fas fa-plus mr-3"></i> Add Customer
                </template>
                <i v-if="is_processing" class="fas fa-spinner fa-spin"></i>
            </button>
        </div>
        <div v-if="is_updating" class="card-body border-top px-4 py-2">
            <a class="small text-danger" data-toggle="modal" data-target="#confirmationModal" href="#">Delete this customer</a>
            <div id="confirmationModal" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-body">
                            <h5 class="modal-title mb-3">Deleting Customer</h5>
                            <p>Are you sure you want to delete the customer "{{ original_name }}"?</p>
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
                calculations: [],
                countries: [],
                deleting_error: null,
                errors: {},
                is_processing: false,
                is_updating: false,
                original_name: '',
                customer: {
                    id: null,
                    name: '',
                    email: '',
                    phone_number: '',
                    street: '',
                    state: '',
                    city: '',
                    postal_code: '',
                    country: '',
                    remark: '',
                },
            }
        },

        mounted() {
            this.countries = Data.countries;

            if (Customer) {
                this.customer.id = Customer.id;
                this.customer.name = Customer.name;
                this.original_name = this.customer.name;
                this.customer.email = Customer.email;
                this.customer.phone_number = Customer.phone_number;
                this.customer.street = Customer.street;
                this.customer.state = Customer.state;
                this.customer.city = Customer.city;
                this.customer.postal_code = Customer.postal_code;
                this.customer.country = Customer.country;
                this.customer.remark = Customer.remark;

                this.is_updating = true;
            } else {
                this.customer.country = Business.country;
            }

            this.customer.currency = Business.currency.toUpperCase();
        },


        methods: {
            saveMethod() {
                this.is_processing = true;

                this.errors = {
                    //
                };

                if (this.customer.id !== null) {
                    axios.put(this.getDomain('business/' + Business.id + '/customer/' + this.customer.id, 'dashboard'), this.customer).then(({data}) => {
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
                    axios.post(this.getDomain('business/' + Business.id + '/customer', 'dashboard'), this.customer).then(({data}) => {
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

                axios.delete(this.getDomain('business/' + Business.id + '/customer/' + this.customer.id, 'dashboard')).then(({data}) => {
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
