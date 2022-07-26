<style scoped>

</style>
<template>
    <div class="col-md-9 col-lg-8 main-content">
        <div class="shiping-pickup">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h3>Shipping</h3>
                <div class="row">
                    <div class="col-12">
                        <div class="item">
                          <SmallSwitch
                            id="switch-shipping"
                            v-model="shipping_enable"
                            onText="Shipping is enabled"
                            offText="Shipping is disabled"
                            @input="changeShipping()"/>
                        </div>
                    </div>
                </div>
            </div>
            <template v-if="shipping_enable">
                <div class="meta-shipping">
                    <div class="card-body pt-0">
                        <p>The shipping method below will be applicable to all shippable products</p>
                        <div class="d-flex justify-content-between pt-2">
                            <a class="btn btn-primary"
                            :href="shippingCreateLink">Add Shipping Method</a>
                            <button v-if="form.id === null" class="btn btn-success" data-toggle="modal"
                                    data-target="#discountModal">Add Shipping Discount
                            </button>
                            <button v-else class="btn btn-success" data-toggle="modal" data-target="#discountModal">Edit
                                Shipping
                                Discount
                            </button>
                        </div>
                    </div>
                </div>
                <div v-if="session_success != null"
                    class="alert alert-success border-left-0 border-right-0 rounded-0 alert-dismissible fade show"
                    role="alert">
                    {{ session_success }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <template v-if="shippings.length > 0">
                    <a v-for="(shipping,key) in shippings" class="hoverable" :href="shippingEditLink(shipping.id)">
                        <div class="card-body bg-light border-top p-4">
                            <div class="media">
                                <div class="media-body">
                                    <p class="fw-500 mb-2">{{ shipping.name }}</p>
                                    <p class="text-dark small mb-2"><span
                                        class="text-muted"># {{ shipping.id }}</span></p>
                                    <p v-if="shipping.description" class="text-dark small mb-2">{{
                                            shipping.description
                                        }}</p>
                                    <p class="text-dark small mb-0">Calculation Method: <span
                                        class="text-muted">{{ shipping.calculation }}</span></p>
                                    <p class="text-dark small mb-0">Rate: <span
                                        class="text-muted">{{ getFormattedAmount(shipping.rate) }}</span>
                                    </p>
                                    <ul class="text-muted list-inline small mt-2 mb-0">
                                        <template v-for="country in countries[key]">
                                            <li v-if="country.code === 'global'" class="list-inline-item"><span
                                                class="fas fa-globe-asia text-primary mr-1"></span>
                                                {{ country.name }}
                                            </li>
                                            <li v-else class="list-inline-item"><span
                                                class="flag-icon shadow-sm mr-1" :class="countryIcon(country.code)"></span>
                                                {{ country.name }}
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </a>
                </template>
                <div v-else class="card-body bg-light border-top p-4">
                    <div class="text-center text-muted py-4">
                        <p><i class="fa fas fa-truck fa-4x"></i></p>
                        <p class="small mb-0">- No shipping found -</p>
                    </div>
                </div>
            </template>
            <div class="card-body p-4">
                <h3>Pickup</h3>
                <div class="row">
                    <div class="col-12">
                        <div class="item">
                          <SmallSwitch
                            id="switch-pickup"
                            v-model="pickup_enable"
                            onText="Pickup is enabled"
                            offText="Pickup is disabled"
                            @input="changePickup()"/>
                        </div>
                    </div>
                </div>
            </div>
            <template v-if="pickup_enable">
                <div class="card-body pt-0">
                    <div class="pickup">
                        <div class="row">
                            <div class="col-12 col-sm-12">
                                <div class="form-group">
                                    <label for="street">Street</label>
                                    <input id="street" type="text" v-model="pickup.street" :class="{
                                        'is-invalid' : errors.pickup_street,
                                    }" class="form-control" title="Street" placeholder="">
                                    <span v-if="errors.pickup_street" class="invalid-feedback" role="alert">{{ errors.pickup_street }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 col-sm-6 col-lg-6">
                                <div class="form-group">
                                    <label for="city">City</label>
                                    <input id="city" type="text" v-model="pickup.city" :class="{
                                        'is-invalid' : errors.city,
                                    }" class="form-control" title="Pickup city" placeholder="">
                                    <span v-if="errors.city" class="invalid-feedback" role="alert">{{ errors.city }}</span>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-6">
                                <div class="form-group">
                                    <label for="state">State</label>
                                    <input id="state" type="text" v-model="pickup.state" :class="{
                                        'is-invalid' : errors.state,
                                    }" class="form-control" title="Pickup state" placeholder="">
                                    <span v-if="errors.state" class="invalid-feedback" role="alert">{{ errors.state }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 col-sm-6 col-lg-6">
                                <div class="form-group">
                                    <label for="postal-code">Postal code</label>
                                    <input id="postal-code" type="text" v-model="pickup.postal_code" :class="{
                                        'is-invalid' : errors.postal_code,
                                    }" class="form-control" title="Postal code" placeholder="">
                                    <span v-if="errors.postal_code" class="invalid-feedback" role="alert">{{ errors.postal_code }}</span>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-6">
                                <div class="form-group">
                                    <label for="pickup-country">Country</label>
                                    <input id="pickup-country" type="text" readonly v-model="pickup.country" :class="{ 'is-invalid' : errors.country }" class="form-control" title="Country" placeholder="">
                                    <span v-if="errors.country" class="invalid-feedback" role="alert">{{ errors.country }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="slots">
                        <div class="row">
                            <div class="col-12 col-sm-12">
                                <label class="fw-500">Time Slots for pick up</label>
                                <p>Add date and time slots for pick up</p>
                                <p class="mb-0">
                                    <a href="#" class="a-link" @click="triggerPickupSlots($event, !pickup.has_slots)">{{ pickup.has_slots ? 'Disable slots' : 'Enable slots' }}</a>
                                </p>
                            </div>
                        </div>
                        <div v-if="pickup.has_slots" class="mt-3">
                            <a v-for="(item, index) in usable_data.week_days_list"
                            v-if="!pickup.slots.find(x => x.day === index)"
                            @click="addPickupSlot($event, index)" class="btn btn-outline-secondary btn-sm mr-1 mb-1" href="#">
                                + {{ item }}
                            </a>
                        </div>
                        <template v-if="pickup.has_slots">
                            <div v-for="slot in pickup.slots">
                                <div class="form-group row mb-2">
                                    <label class="col-12 col-form-label">
                                        <a class="small text-danger float-right" href="#"
                                        @click="removePickupSlot($event, slot.day)">Remove</a>
                                        <span class="fw-500">{{ usable_data.week_days_list[slot.day] }}</span>
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
                        <div class="row mt-2">
                            <div class="col-12 col-sm-12">
                                <button class="btn btn-primary mt-3 mb-3" @click="savePickup()">Save</button>
                            </div>
                        </div>
                        <p v-if="is_succeeded" class="text-success fw-500 mb-0 mt-3">
                            <i class="fas fa-check-circle mr-2"></i> Saved successfully!
                        </p>
                    </div>
                </div>
            </template>
            </div>
        </div>
        <div class="modal fade" id="discountModal" tabindex="-1" role="dialog"
             aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exportModalLabel">Shipping Discount</h5>
                        <button id="closeBtn" type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="business-discount" ref="businessDiscount">
                            <div class="form-group">
                                <label for="minimum_cart_amount">Minimum Purchase Amount (Applies to entire order)<span
                                    class="text-danger">*</span></label>
                                <input id="minimum_cart_amount" type="number" placeholder="10.00" step="0.01" min="0"
                                       v-model="form.minimum_cart_amount"
                                       :class="{'is-invalid' : errors.minimum_cart_amount}"
                                       class="form-control bg-light" title="Minimum cart amount">
                                <span class="invalid-feedback" role="alert"
                                      v-if="errors.minimum_cart_amount">{{ errors.minimum_cart_amount }}</span>
                            </div>
                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" v-model="form.type" type="radio"
                                           name="discount_type" id="percent_discount" value="percent" checked required>
                                    <label class="form-check-label" for="percent_discount">
                                        Percentage(%)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" v-model="form.type" required type="radio"
                                           name="discount_type" id="fixed_discount" value="fixed">
                                    <label class="form-check-label" for="fixed_discount">
                                        Fixed amount
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" v-model="form.type" required type="radio"
                                           name="discount_type" id="free_discount" value="free">
                                    <label class="form-check-label" for="free_discount">
                                        Free
                                    </label>
                                </div>
                            </div>
                            <div v-if="form.type != 'free'" class="form-group">
                                    <label for="value">Discount Value <span class="text-danger">*</span></label>
                                    <input id="value" required type="number"
                                           :placeholder="(form.type =='percent'?'10': '10.00')"
                                           :step="form.type =='percent'?1: 0.01" v-model="form.value" :class="{
                                'is-invalid' : errors.value}" class="form-control bg-light" min="0" title="Discount amount">
                                    <span class="invalid-feedback" role="alert" v-if="errors.value">{{
                                            errors.value
                                        }}</span>
                            </div>
                        </form>
                        <button id="createBtn" class="btn btn-success btn-lg btn-block mb-3 shadow-sm"
                                @click="createDiscount()" :disabled="is_busy">
                            {{ 'Save Changes' }}
                            <i class="fas fa-spin fa-spinner" :class="{
                        'd-none' : !is_busy}"></i></button>
                        <a v-if="form.id != null" :href="deleteDiscountLink" class="text-center">
                            <i class="fa fa-trash"></i> <span>Delete</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import VueTimepicker from 'vue2-timepicker';
import SmallSwitch from '../UI/SmallSwitch'

export default {
    name: "ShippingList",
    components: {
        VueTimepicker,
        SmallSwitch
    },
    props: {
        session_success: {
            type: String,
            default: null
        }
    },
    data() {
        return {
            is_loading: false,
            is_busy: false,
            business: [],
            shippings: [],
            countries: [],
            form: {
                id: null,
                minimum_cart_amount: null,
                type: 'percent',
                fixed_amount: null,
                value: 0,
                percentage: 0
            },
            errors: {},
            is_processing: false,
            is_succeeded: false,
            shipping_enable: false,
            pickup_enable: false,
            pickup: {
                street:"",
                city:"",
                state: "",
                postal_code: "",
                country: "",
                has_slots: false,
                slots: []
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
            },
        };
    },
    watch: {
        form: {
            handler(values) {
                if (values.minimum_cart_amount !== null && values.minimum_cart_amount !== '') {
                    let indexOfPeriodForPrice = values.minimum_cart_amount.toString().indexOf('.');
                    let decimalsLengthForPrice = values.minimum_cart_amount.toString().substr(indexOfPeriodForPrice);

                    if (decimalsLengthForPrice && decimalsLengthForPrice.length > 3) {
                        this.errors.minimum_cart_amount = 'The minimum cart amount can\'t have more than two decimals.';
                    } else {
                        this.errors.minimum_cart_amount = null;
                    }
                }
                if (values.type != 'free' && values.value !== null && values.value !== '' && values.type != 'percent') {
                    let indexOfPeriodForPrice = values.value.toString().indexOf('.');
                    let decimalsLengthForPrice = values.value.substr(indexOfPeriodForPrice);

                    if (decimalsLengthForPrice && decimalsLengthForPrice.length > 3) {
                        this.errors.value = 'The fixed amount can\'t have more than two decimals.';
                    } else {
                        this.errors.value = null;
                    }
                }
            },
        },
        'pickup.slots': {
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
        }
    },

    mounted() {
        this.business = Business;

        if (window.Shippings !== undefined) {
            this.shippings = Shippings;
        }

        if (window.Countries !== undefined) {
            this.countries = Countries;
        }

        if(this.business.enabled_shipping) {
            this.shipping_enable = true;
        }

        if(this.business.can_pick_up) {
            this.pickup_enable = true;
        }

        if (window.ShippingDiscount !== null) {
            this.form.id = ShippingDiscount.id ? ShippingDiscount.id : null;
            this.form.name = ShippingDiscount.name;
            this.form.minimum_cart_amount = Number(ShippingDiscount.minimum_cart_amount / 100).toFixed(2);
            this.form.fixed_amount = Number(ShippingDiscount.fixed_amount / 100).toFixed(2);
            this.form.percentage = ShippingDiscount.percentage;
            this.form.type = ShippingDiscount.type;
            this.form.value = ShippingDiscount.percentage ? (ShippingDiscount.percentage * 100).toFixed(2) : this.form.fixed_amount;
        }

        this.getPickup();

    },
    methods: {
        shippingEditLink(id) {
            return '/business/' + this.business.id + '/setting/shipping/' + id;
        },
        getFormattedAmount(rate) {
            return (rate / 100).toFixed(2) + ' ' + (this.business.currency).toUpperCase();
        },
        countryIcon(code) {
            return 'flag-icon-' + code;
        },
        createDiscount() {
            this.errors = {};
            this.is_busy = true;

            if (this.form.minimum_cart_amount == 0 || this.form.minimum_cart_amount === null) {
                this.errors.minimum_cart_amount = 'Minimum cart amount is required'
            } else if (this.form.name === '') {
                this.errors.name = 'Discount name is required'
            } else if (this.form.value === 0 && this.form.type != 'free') {
                this.errors.value = 'Discount value is required'
            }
            if (this.form.minimum_cart_amount !== null && this.form.minimum_cart_amount !== '') {
                let indexOfPeriodForPrice = this.form.minimum_cart_amount.toString().indexOf('.');
                let decimalsLengthForPrice = this.form.minimum_cart_amount.toString().substr(indexOfPeriodForPrice);

                if (decimalsLengthForPrice && decimalsLengthForPrice.length > 3) {
                    this.errors.minimum_cart_amount = 'The minimum cart amount can\'t have more than two decimals.';
                }
            }
            if (this.form.value !== null && this.form.value !== '' && this.form.type != 'percent' && this.form.type != 'free') {
                let indexOfPeriodForPrice = this.form.value.toString().indexOf('.');
                let decimalsLengthForPrice = this.form.value.substr(indexOfPeriodForPrice);

                if (decimalsLengthForPrice && decimalsLengthForPrice.length > 3) {
                    this.errors.value = 'The fixed amount can\'t have more than two decimals.';
                }
            }

            if (Object.keys(this.errors).length > 0) {
                this.showError(_.first(Object.keys(this.errors)));
            } else {
                if (this.form.type === 'free') {
                    this.form.percentage = 0;
                    this.form.fixed_amount = 0;
                } else if (this.form.type === 'percent') {
                    this.form.percentage = (this.form.value / 100).toFixed(4);
                    this.form.fixed_amount = 0;
                } else {
                    this.form.fixed_amount = this.form.value;
                    this.form.percentage = 0;
                }
                delete this.form.value;
                axios.post(this.getDomain('business/' + this.business.id + '/setting/shipping/discount', 'dashboard'), this.form).then(({data}) => {
                    window.location.href = data.redirect_url;
                });
            }
        },
        triggerPickupSlots(event, status) {
            event.preventDefault();
            this.pickup.has_slots = status;
            if(!status) {
                this.pickup.slots = [];
            }
        },
        addPickupSlot(event, key) {
            event.preventDefault();

            if (this.pickup.slots.length < 7) {
                this.pickup.slots.push({
                    day: key,
                    times: {
                        from: '',
                        to: '',
                    },
                    error: ''
                });
                let slots = this.pickup.slots;
                this.pickup.slots = this.orderSlot(slots);
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
        },
        removePickupSlot(event, key) {
            event.preventDefault();
            let slot = this.pickup.slots.indexOf(this.pickup.slots.find(x => x.day === key));
            this.pickup.slots.splice(slot, 1);
        },
        changeShipping(value) {
            axios.put(this.getDomain(`v1/business/${this.business.id}/shipping/enable`, 'api'), {
                enabled_shipping: this.shipping_enable
            },{ withCredentials: true }).then(response => {

            });
        },
        changePickup(value) {
            axios.put(this.getDomain(`v1/business/${this.business.id}/pick-up`, 'api'), {
                can_pick_up: this.pickup_enable
            },{ withCredentials: true }).then(response => {
            });
        },
        savePickup() {
            this.is_succeeded = false;
            this.is_processing = true;
            this.errors = {}
            if( this.pickup.street == null || this.pickup.street.trim() == '' ) {
                this.errors.pickup_street = 'The street field is required.';
            } else if(this.pickup.street.length > 255) {
                this.errors.pickup_street = 'The street field may not be greater than 255 characters.';
            }

            if( this.pickup.city == null || this.pickup.city.trim() == '' ){
                this.errors.city = 'The city field is required.';
            } else if(this.pickup.city.length > 255) {
                this.errors.city = 'The city field may not be greater than 255 characters.';
            }

            if ( this.pickup.state == null || this.pickup.state.trim() == '' ){
                this.errors.state = 'The state field is required.';
            } else if(this.pickup.state.length > 255) {
                this.errors.state = 'The state field may not be greater than 255 characters.';
            }

            if( this.pickup.postal_code == null || this.pickup.postal_code.trim() == '') {
                this.errors.postal_code = 'The postal code field is required.';
            } else if(this.pickup.postal_code.length > 16) {
                this.errors.postal_code = 'The postal code may not be greater than 16 characters.';
            }

            if (Object.keys(this.errors).length > 0) {
                this.showError(_.first(Object.keys(this.errors)));
                return;
            }

            axios.put(this.getDomain(`v1/business/${this.business.id}/pick-up`, 'api'), {
                can_pick_up: this.pickup_enable,
                slots: this.pickup.slots,
                street: this.pickup.street,
                city: this.pickup.city,
                state: this.pickup.state,
                postal_code: this.pickup.postal_code
            },{ withCredentials: true }).then(response => {
                this.is_succeeded = true;
                this.is_processing = false;
            });
        },
        showError(firstErrorKey) {
            if (firstErrorKey !== undefined) {
                this.scrollTo('#' + firstErrorKey);

                $('#' + firstErrorKey).focus();
            }
            this.is_processing = false;
        },
        getPickup() {
            axios.get(this.getDomain(`v1/business/${this.business.id}/pick-up`, 'api'),{ withCredentials: true }).then(response => {
                let address = response.data.address;
                let slots = response.data.slots;
                if(address) {
                    this.pickup.street = address.street;
                    this.pickup.city = address.city;
                    this.pickup.state = address.state;
                    this.pickup.postal_code = address.postal_code;
                    this.pickup.country = this.business.country_name;
                }
                if(slots) {
                    if(slots.length > 0)
                        this.pickup.has_slots = true;
                    this.pickup.slots = slots;
                }
            });
        }
    },
    computed: {
        shippingCreateLink() {
            return '/business/' + this.business.id + '/setting/shipping/create';
        },
        deleteDiscountLink() {
            return '/business/' + this.business.id + '/setting/shipping/discount/' + this.form.id;
        },
    }
}
</script>
