<template>
    <div>
        <div class="card-body border-top p-4">
            <div class="form-row">
                <div class="col-12 col-sm-8 col-lg-6 mb-3">
                    <label for="country" class="small text-muted text-uppercase">Country</label>
                    <select id="country" class="custom-select bg-light" v-model="shipping.country" :class="{
                        'is-invalid' : errors.country
                    }" :disabled="is_processing">
                        <option v-for="country in countries" :value="country.code">
                            {{ country.name }}
                        </option>
                    </select> <span class="invalid-feedback" role="alert">{{ errors.country }}</span>
                </div>
            </div>
            <div class="form-row">
                <div class="col-12 col-sm-8 col-lg-6 mb-3">
                    <label for="calculation" class="small text-muted text-uppercase">Calculation</label>
                    <select id="calculation" class="custom-select bg-light" v-model="shipping.calculation" :class="{
                        'is-invalid' : errors.calculation
                    }" :disabled="is_processing">
                        <option v-for="calculation in calculations" :value="calculation.code">
                            {{ calculation.name }}
                        </option>
                    </select> <span class="invalid-feedback" role="alert">{{ errors.calculation }}</span>
                </div>
            </div>
            <div class="form-group">
                <label for="name" class="small text-muted text-uppercase">Shipping Method Name</label>
                <input id="name" class="form-control bg-light" title="" v-model="shipping.name" :class="{
                    'is-invalid' : errors.name
                }" placeholder="Free!" :disabled="is_processing">
                <span class="invalid-feedback" role="alert">{{ errors.name }}</span>
            </div>
            <div class="form-group">
                <label for="description" class="small text-muted text-uppercase">Description</label>
                <textarea id="description" v-model="shipping.description" class="form-control bg-light" rows="3"
                          :class="{
                    'is-invalid' : errors.description
                }" placeholder="You can elaborate more about this, e.g. time taken for delivery?"
                          :disabled="is_processing"></textarea>
                <span class="invalid-feedback" role="alert">{{ errors.description }}</span>
            </div>
            <div class="form-row">
                <div class="col-12 col-sm-8 col-lg-6 mb-3">
                    <label for="rate" class="small text-muted text-uppercase">Rate</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                        <span class="input-group-text" :class="{
                            'border-danger bg-danger text-white' : errors.rate
                        }">{{ shipping.currency }}</span>
                        </div>
                        <input id="rate" type="number" class="form-control bg-light" step="0.01" v-model="shipping.rate"
                               :class="{
                            'is-invalid' : errors.rate
                        }" placeholder="9.99" :disabled="is_processing">
                    </div>
                    <span class="small text-danger">{{ errors.rate }}</span>
                </div>
            </div>
            <div class="form-row">
                <div class="col-12 my-4">
                    <a :class="{ 'text-danger' : shipping.has_slots }"
                       @click="triggerSlots($event, !shipping.has_slots)"
                       href="#" class="float-right small">
                        {{ shipping.has_slots ? 'Disable slots' : 'Enable slots' }}
                    </a>
                    <h5 class="font-weight-bold mb-3">Delivery date and time slots</h5>
                    <p class="mb-0">Add date and time slots for your delivery</p>
                    <div v-if="shipping.has_slots" :class="{ 'mt-3' : shipping.slots.length < 3 }">
                        <a v-for="(item, index) in usable_data.week_days_list"
                           v-if="!shipping.slots.find(x => x.day === index)"
                           @click="addSlot($event, index)" class="btn btn-outline-secondary btn-sm mr-1" href="#">
                            + {{ item }}
                        </a>
                    </div>
                    <span class="small text-danger">{{ errors.slots }}</span>

                    <template v-if="shipping.has_slots">
                        <div v-for="slot in shipping.slots">
                            <div class="form-group row mb-0">
                                <label class="col-12 col-form-label">
                                    <a class="small text-danger float-right" href="#"
                                       @click="removeSlot($event, slot.day)">Remove</a>
                                    <span class="font-weight-bold">{{ usable_data.week_days_list[slot.day] }}</span>
                                </label>
                                <div class="col-12">
                                    <span class="mr-2">From: </span>
                                    <vue-timepicker format="hh:mm A" v-model="slot.times.from"
                                                    close-on-complete></vue-timepicker>
                                    <span class="mr-2">To: </span>
                                    <vue-timepicker format="hh:mm A" v-model="slot.times.to"
                                                    close-on-complete></vue-timepicker>
                                    <span v-if="slot.error != ''" class="invalid-feedback d-block"
                                          role="alert">{{ slot.error }}</span>
                                </div>
                            </div>
                        </div>
                    </template>

                </div>
            </div>
            <button class="btn btn-primary" @click.prevent="saveMethod" :disabled="is_processing">
                <template v-if="is_updating">
                    <i class="fas fa-save mr-3"></i> Save
                </template>
                <template v-else>
                    <i class="fas fa-plus mr-3"></i> Add Shipping Method
                </template>
                <i v-if="is_processing" class="fas fa-spinner fa-spin"></i>
            </button>
        </div>
        <div v-if="is_updating" class="card-body border-top px-4 py-2">
            <a class="small text-danger" data-toggle="modal" data-target="#confirmationModal" href="#">Delete this
                shipping method</a>
            <div id="confirmationModal" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-body">
                            <h5 class="modal-title mb-3">Deleting Shipping Method</h5>
                            <p>Are you sure you want to delete the shipping method "{{ original_name }}"?</p>
                            <p v-if="deleting_error" class="font-weight-bold text-danger"><i
                                class="fas fa-exclamation-circle mr-1"></i> {{ deleting_error }}</p>
                            <button type="button" class="btn btn-danger" @click.prevent="deleteMethod"
                                    :disabled="is_processing">
                                <i class="fas fa-times mr-1"></i> Delete <i v-if="is_processing"
                                                                            class="fas fa-spinner fa-spin"></i>
                            </button>
                            <button type="button" class="btn btn-light" data-dismiss="modal" :disabled="is_processing">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import VueTimepicker from 'vue2-timepicker';

import 'vue2-timepicker/dist/VueTimepicker.css';

export default {
    components: {VueTimepicker},
    data() {
        return {
            calculations: [],
            countries: [],
            deleting_error: null,
            errors: {},
            is_processing: false,
            is_updating: false,
            original_name: '',
            shipping: {
                calculation: '',
                country: '',
                currency: '',
                description: '',
                id: null,
                name: '',
                rate: '',
                has_slots: false,
                slots: [],
            },
            usable_data: {
                week_days_list: {
                    Monday: 'Monday',
                    Tuesday: 'Tuesday',
                    Wednesday: 'Wednesday',
                    Thursday: 'Thursday',
                    Friday: 'Friday',
                    Saturday: 'Saturday',
                    Sunday: 'Sunday',
                }
            }
        }
    },
    watch: {
        'shipping.slots': {
            handler(values) {
                if (values.length > 0) {
                    this.errors.slots = '';
                }
                _.forEach(values, function (value) {
                    if (value.times.from != '' && value.times.to != '') {

                        let from = "01/01/2011 " + value.times.from;
                        let to = "01/01/2011 " + value.times.to;
                        let fromDate = new Date(Date.parse(from));
                        let toDate = new Date(Date.parse(to));

                        if (fromDate > toDate) {
                            value.error = "'To' time cannot be earlier than 'from' time"
                        } else {
                            value.error = '';
                        }
                    }
                });
            },
            deep: true
        },

        'shipping.name': function(val) {
            this.errors.name = val.length > 0 ? null : 'The name can\'t be empty';
        },

        'shipping.rate': function(val) {
            this.errors.rate = val.length > 0 ? null : 'The amount can\'t be empty';
        }
    },

    mounted() {
        this.calculations = Data.calculations;
        this.countries = Data.countries;

        if (Shipping) {
            this.shipping.id = Shipping.id;
            this.shipping.calculation = Shipping.calculation;
            this.shipping.country = Shipping.country;
            this.shipping.description = Shipping.description;
            this.shipping.name = Shipping.name;
            if (Shipping.slots != null) {
                this.shipping.slots = this.orderSlot(Shipping.slots);
                this.shipping.has_slots = true;
            }
            this.original_name = this.shipping.name;
            this.shipping.rate = Shipping.rate.toString();

            this.is_updating = true;
        } else {
            this.shipping.calculation = this.calculations[0]['code'];
            this.shipping.country = Business.country;
        }

        this.shipping.currency = Business.currency.toUpperCase();
    },


    methods: {
        saveMethod() {
            this.is_processing = true;

            this.errors = {
                //
            };

            if (this.shipping.country === '') {
                this.errors.country = 'The country is invalid';
            }

            let country = this.countries.find(x => x.code === this.shipping.country);

            if (country === undefined) {
                this.errors.country = 'The country is invalid';
            }

            if (this.shipping.calculation === '') {
                this.errors.calculation = 'The calculation is invalid';
            }

            if (this.shipping.name === '') {
                this.errors.name = 'The name can\'t be empty';
            }

            if (this.shipping.rate === '') {
                this.errors.rate = 'The amount can\'t be empty';
            }

            if(Number.parseInt(this.shipping.rate) < 0 || Number.parseInt(this.shipping.rate) > 9999999){
                this.errors.rate = 'The amount must be from 0 to 9999999';
            }

            let decimalLength = 0;
            let split = this.shipping.rate.split(".");

            if (split.length > 1) {
                decimalLength = split[1].length || 0;
            }

            if (decimalLength > 2) {
                this.is_processing = false;
                return this.errors.rate = 'The amount can\'t have more than 2 decimals.';
            }

            if (this.shipping.has_slots) {
                if (this.shipping.slots.length > 0) {
                    let err = false;
                    _.each(this.shipping.slots, value => {
                        if (value.error != '') {
                            err = true;
                            return;
                        }
                        if (value.times.from === '' || value.times.to === '') {
                            err = true;
                            value.error = "Please fill in both times";
                            return;
                        }
                    });
                    if (err) {
                        this.is_processing = false;
                        return;
                    }
                    this.shipping.slots = JSON.stringify(this.shipping.slots);
                } else {
                    this.errors.slots = 'Please choose date and time slots for your delivery';
                }
            } else {
                this.shipping.slots = null;
            }

            if (Object.keys(this.errors).length > 0) {
                this.showError(_.first(Object.keys(this.errors)));
            } else if (this.shipping.id !== null) {
                axios.put(this.getDomain('business/' + Business.id + '/setting/shipping/' + this.shipping.id, 'dashboard'), this.shipping).then(({data}) => {
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
                axios.post(this.getDomain('business/' + Business.id + '/setting/shipping', 'dashboard'), this.shipping).then(({data}) => {
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

            axios.delete(this.getDomain('business/' + Business.id + '/setting/shipping/' + this.shipping.id, 'dashboard')).then(({data}) => {
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

        triggerSlots(event, status) {
            event.preventDefault();

            this.shipping.has_slots = status;
        },

        addSlot(event, key) {
            event.preventDefault();

            if (this.shipping.slots.length < 7) {
                this.shipping.slots.push({
                    day: key,
                    times: {
                        from: '',
                        to: '',
                    },
                    error: ''
                });
                let slots = this.shipping.slots;
                this.shipping.slots = this.orderSlot(slots);
            }
        },

        orderSlot(slots) {
            let arr = ['', '', '', '', '', '', '']
            slots.forEach(day => {
                if (day.day === 'Monday') arr[0] = day
                if (day.day === 'Tuesday')  arr[1] = day
                if (day.day === 'Wednesday') arr[2] = day
                if (day.day === 'Thursday') arr[3] = day
                if (day.day === 'Friday') arr[4] = day
                if (day.day === 'Saturday') arr[5] = day
                if (day.day === 'Sunday') arr[6] = day
            });

            return arr.filter(str => str !== '')
        },

        removeSlot(event, key) {
            event.preventDefault();

            let slot = this.shipping.slots.indexOf(this.shipping.slots.find(x => x.day === key));

            this.shipping.slots.splice(slot, 1);
        }
    },
}
</script>
