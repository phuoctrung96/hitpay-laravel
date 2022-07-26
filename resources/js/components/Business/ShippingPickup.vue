<style scoped>

</style>
<template>
    <div class="col-md-9 col-lg-8 main-content">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm mb-3">
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-12">
                                <div class="item">
                                    <div class="custom-control custom-switch">
                                        <input id="switch-shipping" v-model="shipping_type.shipping" type="checkbox"
                                            class="custom-control-input">
                                        <label for="switch-shipping" class="custom-control-label">Shipping is enabled</label>
                                    </div>                      
                                </div>
                            </div>
                        </div>
                        <!-- Shipping block -->
                        <template v-if="shipping_type.shipping">
                            <div class="shiping">
                                <div class="row">
                                    <div class="col-12 col-sm-6 col-lg-6">
                                        <div class="form-group">
                                            <label for="country">Country</label>
                                            <select id="country" class="custom-select is-dropdown" v-model="shipping.country_id" :class="{
                                                'is-invalid' : errors.country_id
                                            }" :disabled="is_processing">
                                                <option v-for="country in countries" :value="country.code" :key="country.code">
                                                    {{ country.name }}
                                                </option>
                                            </select> <span class="invalid-feedback" role="alert">{{ errors.country_id }}</span>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6 col-lg-6">
                                        <div class="form-group">
                                            <label for="calculation">Calculation</label>
                                            <select id="calculation" class="custom-select is-dropdown" v-model="shipping.calculation" :class="{
                                                'is-invalid' : errors.calculation
                                            }" :disabled="is_processing">
                                                <option v-for="calculation in calculations" :value="calculation.code">
                                                    {{ calculation.name }}
                                                </option>
                                            </select> <span class="invalid-feedback" role="alert">{{ errors.calculation }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 col-sm-6 col-lg-6">
                                        <div class="form-group">
                                            <label for="method_name">Shipping method name</label>
                                            <input id="method_name" type="text" v-model="shipping.method_name" :class="{
                                                'is-invalid' : errors.method_name,
                                            }" class="form-control" title="Shipping method name" placeholder="">
                                            <span v-if="errors.method_name" class="invalid-feedback" role="alert">{{ errors.method_name }}</span>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6 col-lg-6">
                                        <label for="rate">Rate</label>
                                        <div class="input-group">
                                            <input id="rate" type="number" class="form-control is-form-arrow" step="0.01" v-model="shipping.rate"
                                                :class="{
                                                'is-invalid' : errors.rate
                                            }" placeholder="9.99" :disabled="is_processing">
                                            <div class="input-group-prepend">
                                            <span class="input-group-text" :class="{
                                                'border-danger bg-danger text-white' : errors.rate
                                            }">{{ shipping.currency }}</span>
                                            </div>
                                        </div>
                                        <span class="small text-danger">{{ errors.rate }}</span>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 col-sm-12">
                                        <div class="form-group">
                                            <label for="description">Description</label>
                                            <textarea  id="description" class="form-control" v-model="shipping.description" :class="{
                                                'is-invalid' : errors.description}" title="Description" placeholder=""></textarea>
                                            <span v-if="errors.description" class="invalid-feedback" role="alert">{{ errors.description }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 col-sm-12">
                                        <label>Delivery date and time slots</label>
                                        <p>Add date and time slots for your delivery</p>
                                        <p class="enable">
                                            <a href="#">Enable slots</a>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div class="row mt-2">
                            <div class="col-12">
                                <div class="item">
                                    <div class="custom-control custom-switch">
                                        <input id="switch-pickup" v-model="shipping_type.pickup" type="checkbox"
                                            class="custom-control-input">
                                        <label for="switch-pickup" class="custom-control-label">Pickup is enabled</label>
                                    </div>                      
                                </div>
                            </div>
                        </div>
                        <!-- Shipping block -->
                        <template v-if="shipping_type.pickup">
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
                                            <input id="pickup-country" type="text" v-model="pickup.country" :class="{
                                                'is-invalid' : errors.country,
                                            }" class="form-control" title="Country" placeholder="">
                                            <span v-if="errors.country" class="invalid-feedback" role="alert">{{ errors.country }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12 col-sm-12">
                                        <label>Delivery date and time slots</label>
                                        <p>Add date and time slots for your delivery</p>
                                        <p class="enable">
                                            <a href="#">Enable slots</a>
                                        </p>
                                    </div>
                                </div>
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
    name: "ShippingPickup",

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
            shipping_type: {
                shipping: false,
                pickup: false,
            },
            shipping: {
                country_id: 0,
                calculation:0,
                method_name:"",
                rate: 0,
                description: "",
                currency: "SGD"
            },
            pickup: {
                street:"",
                city:"",
                state: "", 
                postal_code: "", 
                country: ""
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
    },

    mounted() {
        this.business = Business;

        if (window.Shippings !== undefined) {
            this.shippings = Shippings;
        }

        if (window.Countries !== undefined) {
            this.countries = Countries;
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

    },
    methods: {
        
    },
    computed: {
        
    }
}
</script>
