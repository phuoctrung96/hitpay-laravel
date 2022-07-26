<style scoped>
.form-stack {
    position: relative;
    display: -webkit-inline-box;
    display: inline-flex;
    vertical-align: middle;
    width: 100%;
}

.form-stack > .form-control {
    width: 100%;
    position: relative;
    -webkit-box-flex: 1;
    flex: 1 1 auto;
}

.form-stack > .form-control:focus {
    z-index: 1;
}

.form-stack {
    -webkit-box-orient: vertical;
    -webkit-box-direction: normal;
    flex-direction: column;
    -webkit-box-align: start;
    align-items: flex-start;
    -webkit-box-pack: center;
    justify-content: center;
}

.form-stack > .form-control:not(:first-child) {
    margin-top: -1px;
    border-top-left-radius: 0;
    border-top-right-radius: 0;
}

.form-stack > .form-control:not(:last-child) {
    border-bottom-right-radius: 0;
    border-bottom-left-radius: 0;
}

/* Payment methods */

.alert-border {
    border-left: none;
    border-right: none;
    border-radius: 0;
}

::placeholder {
    font-size: 87.5%;
    color: #cecece;
}

#payment-methods {
    margin: 0 0 8px;
    border-bottom: 2px solid #e8e8fb;
}

#payment-methods li {
    display: inline-block;
    margin: 0 16px 0 0;
    list-style: none;
}

#payment-methods input {
    display: none;
}

#payment-methods label {
    display: flex;
    flex: 1;
    cursor: pointer;
    margin-bottom: 0;
}

#payment-methods input + label {
    position: relative;
    padding: 0 0 4px 0;
    text-decoration: none;
    text-transform: uppercase;
    font-size: 87.5%;
}

#payment-methods label::before {
    content: '';
    position: absolute;
    width: 100%;
    bottom: -2px;
    left: 0;
    border-bottom: 2px solid #6772e5;
    opacity: 0;
    transform: scaleX(0);
    transition: all 0.25s ease-in-out;
}

#payment-methods label:hover {
    color: #6772e5;
    cursor: pointer;
}

#payment-methods label.disabled {
    color: #6c757d;
}

#payment-methods label.disabled:hover {
    color: #6c757d;
    cursor: default;
}

#payment-methods input:checked + label {
    color: #6772e5;
}

#payment-methods label:not(.disabled):hover::before,
#payment-methods input:checked + label::before {
    opacity: 1;
    transform: scaleX(1);
}

.payment-info {
    display: none;
}

#wechat-qrcode img {
    margin: 0 auto;
}
</style>

<template>
    <div>
        <div class="bg-white rounded shadow-sm p-3 mb-3">
            <div id="payment-request" class="mb-3">
                <div id="payment-request-button">
                    <div class="text-center col-auto col-sm-10 col-md-9 mx-auto">
                        <div class="mb-2">
                            <img :src="getDomain('icons/payment-brands/apple-pay.svg', 'shop')" height="36">
                            <img :src="getDomain('icons/payment-brands/g-pay.png', 'shop')" class="border border-dark rounded" height="36">
                        </div>
                        <p class="small mb-0">To checkout with Apple Pay or Google Pay, open this link in Safari (iPhone/iPad/Mac) or Chrome (Android/ Chrome Desktop)</p>
                    </div>
                </div>
            </div>
            <p class="text-center small">{{ text.payment_form.header }}</p>
            <h2 class="h6 text-uppercase text-primary font-weight-bold mb-3">Buyer Details</h2>
            <div id="buyer_details_errors" v-if="errors.buyer_details.length > 0" class="alert alert-danger">
                <ul class="list-unstyled mb-0">
                    <li v-for="message in errors.buyer_details">{{ message }}</li>
                </ul>
            </div>
            <div class="form-stack mb-3">
                <input v-model="checkout.name" id="name" title="Name" class="form-control bg-light" placeholder="Full name" :disabled="is_processing">
                <input v-model="checkout.email" id="email" title="Email" class="form-control bg-light" placeholder="Email address" :disabled="is_processing">
                <input v-model="checkout.phone_number" id="phone_number" type="tel" title="Phone Number" class="form-control bg-light" placeholder="Phone Number" :disabled="is_processing">
            </div>
            <div v-if="business.can_pick_up" class="form-group">
                <div class="custom-control custom-checkbox">
                    <input v-model="checkout.customer_pickup" type="checkbox" class="custom-control-input" id="isPickingUp" :disabled="is_processing || has_shipping === false">
                    <label class="custom-control-label" for="isPickingUp">I want to pickup the product</label>
                </div>
                <small class="form-text text-muted">Pickup Address: {{ business.address_line }}</small>
            </div>
            <div v-if="has_shipping && !checkout.customer_pickup">
                <h2 class="h6 text-uppercase text-primary font-weight-bold mb-3">Shipping Information</h2>
                <div id="shipping_details_errors" v-if="errors.shipping_details.length > 0" class="alert alert-danger">
                    <ul class="list-unstyled mb-0">
                        <li v-for="message in errors.shipping_details">{{ message }}</li>
                    </ul>
                </div>
                <div class="form-stack mb-3">
                    <input v-model="checkout.shipping.street" id="street" class="form-control bg-light" placeholder="Street" title="Street" :disabled="is_processing">
                    <input v-model="checkout.shipping.city" id="city" class="form-control bg-light" placeholder="City" title="City" :disabled="is_processing">
                    <input v-model="checkout.shipping.state" id="state" class="form-control bg-light" placeholder="State" title="State" :disabled="is_processing">
                    <input v-model="checkout.shipping.postal_code" id="postal_code" class="form-control bg-light" placeholder="Postal code" title="Postal code" :disabled="is_processing">
                    <select v-model="checkout.shipping.country" id="country" class="country form-control custom-select bg-light" title="Country" :disabled="is_processing">
                        <option v-for="country in countries" :value="country.code">{{ country.name }}</option>
                    </select>
                </div>
                <div class="mb-3">
                    <div class="details">
                        <select v-model="checkout.shipping.option" id="shipping_option" class="form-control custom-select bg-light" title="Shipping Method" :disabled="!checkout.shipping.country || is_processing">
                            <option value="default" hidden>Select Shipping Option</option>
                            <option v-for="shipping in available_shipping_options" :value="shipping.id">
                                {{ shipping.name }}
                            </option>
                        </select>
                    </div>
                    <span v-if="notes.shipping" class="form-text text-muted small">{{ notes.shipping }}</span>
                </div>
            </div>
            <div class="d-flex justify-content-sm-between align-items-center mb-3">
                <h2 class="h6 text-uppercase text-primary font-weight-bold mb-0">Payment Information</h2>
                <div style="line-height: 1">
                    <img :src="getDomain('icons/payment-brands/visa.png', 'shop')" height="14">
                    <img :src="getDomain('icons/payment-brands/master.png', 'shop')" height="14">
                    <img :src="getDomain('icons/payment-brands/amex.png', 'shop')" height="14">
                </div>
            </div>
            <nav id="payment-methods" v-if="payment.enabled.alipay || payment.enabled.wechat">
                <ul class="m-0 p-0">
                    <li>
                        <input type="radio" v-model="payment.method" id="payment-card" value="card" :disabled="is_processing">
                        <label for="payment-card" :class="{
                            disabled: is_processing
                        }">Card</label>
                    </li>
                    <li :class="{
                        'd-none': !payment.enabled.alipay
                    }">
                        <input type="radio" v-model="payment.method" id="payment-alipay" value="alipay" :disabled="is_processing">
                        <label for="payment-alipay" :class="{
                            disabled: is_processing
                        }">Alipay</label>
                    </li>
                    <li :class="{
                        'd-none': !payment.enabled.wechat
                    }">
                        <input type="radio" v-model="payment.method" id="payment-wechat" value="wechat" :disabled="is_processing">
                        <label for="payment-wechat" :class="{
                            disabled: is_processing
                        }">WeChat Pay</label>
                    </li>
                </ul>
            </nav>
            <div class="form-group payment-info" :class="{
                'd-block' : payment.chosen === 'card'
            }">
                <div id="card-element" class="form-control bg-light p-2"></div>
                <small v-if="stripe.card.error" class="form-text text-danger">{{ stripe.card.error }}</small>
            </div>
            <div class="form-group payment-info" :class="{
                'd-block' : payment.chosen === 'alipay'
            }">
                <div class="alert alert-primary">You’ll be redirected to the banking site to complete your payment.</div>
            </div>
            <div class="form-group payment-info " :class="{
                'd-block' : payment.chosen === 'wechat'
            }">
                <div id="wechat-qrcode" :class="{
                    'border rounded p-3': payment.qr_code
                }"></div>
                <div v-if="!payment.qr_code" class="alert alert-primary">Click the button below to generate a QR code for WeChat.</div>
            </div>
            <button type="submit" id="submitBtn" class="btn btn-block btn-primary py-2" @click.prevent="payNow" :disabled="is_processing">
                <span class="text-white-50">Pay </span> <strong>{{ text.button_amount }}</strong>
            </button>
            <div id="failedMessage" class="alert alert-danger small py-2 mt-2 mb-0 d-none"></div>
            <div class="col-sm-8 text-center text-muted small mt-2 mx-auto">{{text.discount_text}}</div>
            <div class="col-sm-8 text-center text-muted small mt-2 mx-auto">Your card details are never stored on our servers and is fully encrypted for payment processing</div>
        </div>
        <div id="processing" class="modal fade" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog" role="document">
                <div class="modal-content border-0 shadow">
                    <div class="modal-body text-center">
                        <template v-if="processing_step === 1">
                            <h3 class="h3-rs text-primary mb-3">Hang on Tight</h3>
                            <p>Your payment is being processed…</p>
                            <p class="text-danger text-uppercase font-weight-bold">Please do not refresh</p>
                            <i class="fas fa-2x fa-spinner fa-spin text-success"></i>
                        </template>
                        <template v-else-if="processing_step === 2">
                            <p>Thank you purchasing from</p>
                            <h3 class="h3-rs text-primary mb-3">{{ business.name }}</h3>
                            <p>Your order has been received<br>Please check your email for order confirmation</p>
                            <p><img :src="getDomain('icons/done.png', 'shop')" height="32"></p>
                        </template>
                    </div>
                    <div class="modal-footer d-flex justify-content-center">
                        <img :src="getDomain('hitpay/logo-000036.png', 'shop')" height="20">
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    watch: {
        'checkout.shipping.country': {
            handler(country) {
                this.available_shipping_options = [];

                if (this.shipping_options[country] === undefined) {
                    if (this.shipping_options['global'] !== undefined) {
                        this.available_shipping_options = this.shipping_options['global']['options'];
                    }
                } else {
                    this.available_shipping_options = this.shipping_options[country]['options'];
                }

                if (this.available_shipping_options.length > 0) {
                    let detected = _.minBy(this.available_shipping_options, (option) => {
                        return option.rate;
                    });

                    if (detected) {
                        this.checkout.shipping.option = detected.id;
                    }
                }
            },
            deep: true
        },

        'checkout.shipping.option': {
            handler(option) {
                let tempShippingOption = _.find(this.available_shipping_options, [
                    'id',
                    option,
                ]);

                if (tempShippingOption) {
                    this.add_to_cart.shipping_calculation = tempShippingOption.calculation;
                    this.add_to_cart.shipping_rate = tempShippingOption.rate;
                    this.add_to_cart.shipping_rate_stored = tempShippingOption.rate_stored;

                    this.notes.shipping = tempShippingOption.rate_display + ' '
                        + tempShippingOption.calculation_name;

                    if (tempShippingOption.description) {
                        this.notes.shipping = this.notes.shipping + ' - ' + tempShippingOption.description;
                    }
                }

                this.updateTotalAmount();
            },
            deep: true
        },
    },

    data() {
        return {
            add_to_cart: {
                shipping_calculation: '',
                shipping_rate: 0,
                shipping_rate_stored: 0,
            },
            available_shipping_options: [],
            modal: null,
            business: {
                can_pick_up: false,
                address_line: '',
            },
            checkout: {
                name: '',
                email: '',
                phone_number: '',
                customer_pickup: false,
                shipping: {
                    street: '',
                    state: '',
                    city: '',
                    postal_code: '',
                    country: '',
                    option: '',
                },
            },
            errors: {
                buyer_details: [],
                shipping_details: [],
            },
            is_processing: false,
            order: {
                total_quantity: 0,
                total_amount: 0,
                total_amount_stored: 0,
                total_amount_calculated: 0,
            },
            discount: {
                name: '',
                amount: 0,
            },
            countries: {},
            disabling: {
                quantity: false,
            },
            has_shipping: false,
            is_initiating: true,
            notes: {
                shipping: '',
            },
            payment: {
                chosen: 'card',
                enabled: {
                    alipay: false,
                    wechat: false,
                },
                method: '',
                qr_code: '',
            },
            processing_step: 1,
            shipping_options: [],
            stripe: {
                card: {
                    element: null,
                    error: '',
                },
                element: null,
                object: null,
                publishable_key: '',
                payment_request: {
                    element: null,
                    is_loading: false,
                    label: 'Total includes shipping',
                }
            },
            text: {
                discount_text: '',
                button_amount: '',
                payment_form: {
                    header: 'Or enter your shipping and payment details below',
                },
            },
        };
    },

    mounted() {
        this.business = Business;
        this.countries = CheckoutOptions.countries_list;
        this.discount = Discount;
        this.shipping_options = CheckoutOptions.shippings;
        this.has_shipping = Object.keys(this.shipping_options).length > 0;

        if (this.has_shipping === false) {
            this.checkout.customer_pickup = true;
        }

        this.stripe.publishable_key = CheckoutOptions.stripe.publishable_key;
        if (this.stripe.publishable_key.includes('test')) {
            $('#demo').removeClass('d-none');
        }
        this.stripe.object = Stripe(this.stripe.publishable_key, {
            betas: [
                'payment_intent_beta_3',
            ],
        });
        this.stripe.element = this.stripe.object.elements();

        if (!this.has_shipping) {
            this.stripe.payment_request.label = 'Total';
            this.text.payment_form.header = 'Or enter your payment details below';
        }

        if (Discount.name)this.text.discount_text = Discount.name + " discount was applied";

        this.order.total_quantity = TotalCartQuantity;
        this.order.total_amount_calculated = TotalCartAmount - Discount.amount;

        this.stripe.payment_request.element = this.stripe.object.paymentRequest({
            country: this.business.country.toUpperCase(),
            currency: this.business.currency,
            total: {
                label: this.stripe.payment_request.label,
                amount: parseInt(this.order.total_amount_stored), // cart amount
            },
            requestShipping: this.has_shipping,
            requestPayerName: true,
            requestPayerEmail: true,
        });

        this.stripe.payment_request.element.canMakePayment().then(async () => {
            this.stripe.payment_request.element.on('source', async event => {
                this.modal.modal('show');

                let result = await this.createOrder({
                    name: event.shippingAddress.name ? event.shippingAddress.name : event.payerName,
                    email: event.payerEmail,
                    phone_number: event.shippingAddress.phone ? event.shippingAddress.phone : event.payerPhone,
                    quantity: this.add_to_cart.quantity,
                    customer_pickup: this.checkout.customer_pickup,
                    shipping: {
                        address: {
                            street: event.shippingAddress.addressLine[0],
                            city: event.shippingAddress.city,
                            postal_code: event.shippingAddress.postalCode,
                            state: event.shippingAddress.region,
                            country: event.shippingAddress.country.toLowerCase(),
                        },
                        option: event.shippingOption.id,
                    },
                }).catch(({response}) => {
                    if (response.status === 422) {
                        event.complete('fail');
                    }
                });

                const {
                    paymentIntent,
                    error
                } = await this.stripe.object.confirmPaymentIntent(result.data.payment_intent, {
                    source: event.source.id,
                    use_stripe_sdk: true,
                });

                if (error) {
                    event.complete('fail');
                } else if (paymentIntent.status === 'succeeded') {
                    event.complete('success');
                    this.processing_step = 2;
                } else if (paymentIntent.status === 'processing') {
                    event.complete('success');
                    this.processing_step = 2;
                } else if (paymentIntent.status === 'requires_source_action' || paymentIntent.status === "requires_action") {
                    event.complete('success');
                    this.processing_step = 2;

                    const {
                        error: handleError
                    } = await this.stripe.object.handleCardPayment(result.data.payment_intent);

                    if (handleError) {
                        event.complete('fail');
                        this.processing_step = 3;
                    } else {
                        event.complete('success');
                        this.processing_step = 2;
                    }
                } else {
                    event.complete('fail');
                    this.processing_step = 3;
                }
            });

            await this.stripe.payment_request.element.on('shippingoptionchange', async event => {
                this.checkout.shipping.option = event.shippingOption.id;

                this.stripe.payment_request.is_loading = true;
                let tempAmount = await this.updateTotalAmount(true);
                this.stripe.payment_request.is_loading = false;

                await event.updateWith({
                    status: 'success',
                    total: {
                        label: this.stripe.payment_request.label,
                        amount: tempAmount,
                    },
                });
            });

            this.stripe.payment_request.element.on('cancel', event => {
                this.is_processing = false;
            });

            this.stripe.payment_request.element.on('shippingaddresschange', async event => {
                let options = null;

                if (this.shipping_options[event.shippingAddress.country.toLowerCase()]) {
                    options = this.shipping_options[event.shippingAddress.country.toLowerCase()];
                } else if (this.shipping_options['global']) {
                    options = this.shipping_options['global'];
                }

                if (options) {
                    this.checkout.shipping.country = event.shippingAddress.country.toLowerCase();

                    let shippingOptions = [];
                    let firstShippingOptionIsSet = null;

                    await _.each(options.options, async (option) => {
                        if (firstShippingOptionIsSet === null) {
                            firstShippingOptionIsSet = option.id;
                        }

                        let temporary = {
                            id: option.id,
                            label: option.name,
                            amount: option.rate_stored,
                        };

                        if (option.description) {
                            temporary.detail = option.description;
                        }

                        shippingOptions.push(temporary);
                    });

                    if (firstShippingOptionIsSet !== null) {
                        this.checkout.shipping.option = firstShippingOptionIsSet;
                    }

                    this.stripe.payment_request.is_loading = true;
                    let tempAmount = await this.updateTotalAmount(true);
                    this.stripe.payment_request.is_loading = false;

                    await event.updateWith({
                        status: 'success',
                        shippingOptions: shippingOptions,
                        total: {
                            label: this.stripe.payment_request.label,
                            amount: tempAmount,
                        },
                    });
                } else {
                    await event.updateWith({
                        status: 'invalid_shipping_address',
                    });
                }
            });

        });

        this.is_initiating = false;
        this.checkout.shipping.country = this.business.country;

        this.setupCardPayment();

        this.updateTotalAmount();

        this.modal = $('#processing');
    },

    methods: {
        setupCardPayment() {
            this.stripe.card = this.stripe.element.create('card', {
                hidePostalCode: true,
                style: {
                    base: {
                        iconColor: '#000036',
                        color: '#495057',
                        fontWeight: 400,
                        fontFamily: 'Inter, sans-serif',
                        fontSmoothing: 'antialiased',
                        fontSize: '16px',
                        '::placeholder': {
                            color: '#6c757d',
                            fontWeight: 400,
                            fontFamily: 'Inter, sans-serif',
                            fontSize: '16px',
                        },
                    },
                    invalid: {
                        iconColor: '#dc3545',
                        color: '#dc3545',
                    },
                }
            });

            this.stripe.card.mount('#card-element');
            this.stripe.card.on('change', ({error}) => {
                if (error) {
                    this.stripe.card.error = error.message;
                } else {
                    this.stripe.card.error = null;
                }
            });
        },

        updateTotalAmount(returnResult = false) {
            let shipping_rate = 0;
            let shipping_rate_stored = 0;

            if (this.add_to_cart.shipping_calculation === 'flat') {
                shipping_rate = this.add_to_cart.shipping_rate;
                shipping_rate_stored = this.add_to_cart.shipping_rate_stored;
            } else if (this.add_to_cart.shipping_calculation === 'fee_per_unit') {
                shipping_rate = this.add_to_cart.shipping_rate * this.add_to_cart.quantity;
                shipping_rate_stored = this.add_to_cart.shipping_rate_stored * this.add_to_cart.quantity;
            }

            this.order.total_amount = (this.order.total_amount_calculated / 100) + shipping_rate;
            this.order.total_amount_stored = this.order.total_amount_calculated + shipping_rate_stored;

            let tempAmount = Number(this.order.total_amount).toFixed(2).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,').toString();

            this.text.button_amount = this.business.currency.toUpperCase() + tempAmount;

            if (returnResult) {
                return this.order.total_amount_stored;
            }
        },

        async createOrder(submissionData) {
            return await axios.post(this.getDomain(this.business.id + '/checkout', 'shop'), submissionData);
        },

        async payNow() {
            this.is_processing = true;

            this.errors.buyer_details = [];
            this.errors.shipping_details = [];

            if (this.checkout.name === '') {
                this.errors.buyer_details.push('You must enter your name.');
            }

            if (this.checkout.email === '') {
                this.errors.buyer_details.push('You must enter your email.');
            }

            if (this.checkout.customer_pickup === false) {
                if (this.checkout.shipping.street === '') {
                    this.errors.shipping_details.push('The shipping street can\'t be empty.');
                }

                if (this.checkout.shipping.state === '') {
                    this.errors.shipping_details.push('The shipping state can\'t be empty.');
                }

                if (this.checkout.shipping.city === '') {
                    this.errors.shipping_details.push('The shipping city can\'t be empty.');
                }

                if (this.checkout.shipping.postal_code === '') {
                    this.errors.shipping_details.push('The shipping postal_code can\'t be empty.');
                }

                if (this.checkout.shipping.country === '') {
                    this.errors.shipping_details.push('The shipping country can\'t be empty.');
                }

                if (this.checkout.shipping.option === '') {
                    this.errors.shipping_details.push('The shipping street can\'t be empty.');
                }
            }

            if (this.errors.buyer_details.length > 0) {
                this.scrollTo('#buyer_details_errors');
                this.is_processing = false;

                return;
            }

            if (this.errors.shipping_details.length > 0) {
                this.scrollTo('#shipping_details_errors');
                this.is_processing = false;

                return;
            }

            this.modal.modal('show');

            let submissionData = {
                name: this.checkout.name,
                email: this.checkout.email,
                phone_number: this.checkout.phone_number,
                customer_pickup: this.checkout.customer_pickup,
                shipping: {
                    address: {
                        street: this.checkout.shipping.street,
                        city: this.checkout.shipping.city,
                        postal_code: this.checkout.shipping.postal_code,
                        state: this.checkout.shipping.state,
                        country: this.checkout.shipping.country.toLowerCase(),
                    },
                    option: this.checkout.shipping.option,
                },
                discount: {
                    name: this.discount.name,
                    amount: this.discount.amount
                }
            };

            await this.stripe.object.createSource(this.stripe.card).then(async result => {
                if (result.error) {
                    this.stripe.card.error = result.error.message;
                } else {
                    const response = await this.createOrder(submissionData);
                    const {
                        paymentIntent,
                        error
                    } = await this.stripe.object.confirmPaymentIntent(response.data.payment_intent, {
                        source: result.source.id,
                        use_stripe_sdk: true,
                    });

                    if (error) {
                        this.stripe.card.error = error.message;
                    } else {
                        if (paymentIntent.status === 'succeeded') {
                            this.processing_step = 2;
                        } else if (paymentIntent.status === 'requires_source_action' || paymentIntent.status === "requires_action") {
                            const {
                                error: handleError
                            } = await this.stripe.object.handleCardPayment(response.data.payment_intent);

                            if (handleError) {
                                this.processing_step = 3;
                            } else {
                                this.processing_step = 2;
                            }
                        }
                    }
                }
            });
        },

        getOrderStatus() {
            console.log(new Date);
        },
    },
}
</script>
