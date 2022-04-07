<template>
    <div>

        <div class="form-group">
            <input id="cardholder-name" type="text" class="form-control bg-light p-2" :class="{
                'is-invalid': errors.name,
            }" v-model="name" :disabled="is_processing" placeholder="Cardholder Name">
            <small class="invalid-feedback">{{ errors.name }}</small>
        </div>
        <div class="form-group card-payment pb-4">
            <div id="card-element" class="form-control bg-light p-2" :class="{
                'border-danger': errors.card,
            }"></div>
            <small v-if="errors.card" class="text-danger small">{{ errors.card }}</small>
        </div>
        <CheckoutButton
            :title="button_text"
            :dots="is_processing"
            :backColor="backColor"
            :foreColor="foreColor"
            class="checkout-button mx-auto mt-5"
            @click="addCard()"
        />
    </div>
</template>

<script>
import CheckoutButton from "./Shop/CheckoutButton";

    export default {
        watch: {},

        components: {
            CheckoutButton,
        },

        props:{
            backColor: "",
            foreColor: ""
        },

        data() {
            return {
                button_text: 'Add Card',
                errors: {
                    card: null,
                    name: null,
                },
                is_processing: false,
                is_invalid: false,
                name: '',
                card_entered: false,
                stripe: {
                    card: null,
                    element: null,
                    object: null,
                    publishable_key: '',
                },
            };
        },

        mounted() {
            this.name = Name;
            this.stripe.publishable_key = StripePublishableKey;

            if (HasPaymentMethod) {
                this.button_text = 'Update Card';
            }

            if (this.stripe.publishable_key.includes('test')) {
                $('#demo').removeClass('d-none');
            }

            this.stripe.object = Stripe(this.stripe.publishable_key, {
                betas: [
                    'payment_intent_beta_3',
                ],
            });

            this.stripe.element = this.stripe.object.elements();
            this.stripe.card = this.stripe.element.create('card', {
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
                this.card_entered = true;

                if (error) {
                    this.errors.card = error.message;
                } else {
                    this.errors.card = null;
                }
            });
        },

        methods: {
            async addCard() {
                this.is_processing = true;
                this.errors.card = null;
                this.errors.name = null;

                $('#direct-debit-tab').addClass('disabled');

                if (this.name.length < 1) {
                    this.is_processing = false;
                    this.errors.name = 'The cardholder name field is required.';

                    return;
                }

                let setupIntentResponse = await axios.post(window.location.href + "/setup-intent");

                if (setupIntentResponse.status !== 200) {
                    alert('Some error occurred, please refresh and try again. If the error still exists, please contact HitPay.')

                    return;
                }

                const {
                    setupIntent,
                    error
                } = await this.stripe.object.confirmCardSetup(
                    setupIntentResponse.data.client_secret,
                    {
                        payment_method: {
                            card: this.stripe.card,
                            billing_details: {
                                name: this.name,
                            },
                        },
                    }
                )

                if (error) {
                    this.is_processing = false;
                    this.errors.card = error.message;
                } else {
                    axios.post(window.location.href, {
                        payment_method_id: setupIntent.payment_method,
                    }).then(({data}) => {
                        if (data.redirect_url) {
                            window.location.href = data.redirect_url;
                        }
                        else location.reload();
                    }).catch(({response}) => {
                        if (response.status !== 422) {
                            this.is_invalid = true;
                        }

                        this.is_processing = false;
                        this.errors.card = response.data.message;
                    });
                }
            },
        },
    }
</script>
<style>
.card-payment{
    border-bottom: 1px solid #E6E8EC;
}
</style>
