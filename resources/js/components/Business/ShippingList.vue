<style scoped>

</style>
<template>
    <div class="col-md-9 col-lg-8 main-content">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <p>The shipping method below will be applicable to all shippable products</p>
                <div class="d-flex justify-content-between">
                    <a class="btn btn-primary"
                       :href="shippingCreateLink">Add
                        Shipping Method</a>
                    <button v-if="form.id === null" class="btn btn-success" data-toggle="modal"
                            data-target="#discountModal">Add Shipping
                        Discount
                    </button>
                    <button v-else class="btn btn-success" data-toggle="modal" data-target="#discountModal">Edit
                        Shipping
                        Discount
                    </button>
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
                                <p class="font-weight-bold mb-2">{{ shipping.name }}</p>
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
        </div>
        <div class="modal fade" id="discountModal" tabindex="-1" role="dialog"
             aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog" role="document">
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
                            'is-invalid' : errors.value,
                        }" class="form-control bg-light" min="0" title="Discount amount">
                                <span class="invalid-feedback" role="alert" v-if="errors.value">{{
                                        errors.value
                                    }}</span>
                            </div>
                        </form>
                        <button id="createBtn" class="btn btn-success btn-lg btn-block mb-3 shadow-sm"
                                @click="createDiscount()" :disabled="is_busy">
                            {{ 'Save Changes' }}
                            <i class="fas fa-spin fa-spinner" :class="{
                        'd-none' : !is_busy
                    }"></i></button>
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

export default {
    name: "ShippingList",

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
                console.log(this.form);
                axios.post(this.getDomain('business/' + this.business.id + '/setting/shipping/discount', 'dashboard'), this.form).then(({data}) => {
                    window.location.href = data.redirect_url;
                });
            }
        },
        showError(firstErrorKey) {
            this.is_busy = false;
        },
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
