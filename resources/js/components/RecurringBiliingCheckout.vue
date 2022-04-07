<template>
    <div>
        <div class="checkout d-flex flex-column">
            <div class="checkout-main d-flex flex-grow-1">
                <div
                    class="left-panel"
                    :class="safeTheme"
                    :style="leftPanelStyles">

                    <div class="left-recurring-main">
                        <div
                            v-if="business_image"
                            class="business-image mb-5">
                            <img class="mr-3" height="48" :src="business_image"/>
                            <span>{{ business.name }}</span>
                        </div>
                        <div class="recurring-subscribe mb-1">
                            <span>
                                Subscribe to {{ recurring_plan.name }}
                            </span>
                        </div>
                        <div v-if="recurring_plan.description" class="recurring-description mb-4">
                            <span v-if="recurring_plan.description">
                                {{ recurring_plan.description }}
                            </span>
                        </div>
                        <div class="d-flex recurring-price mb-4">
                            <span class="price">{{ recurring_plan.price }}</span>
                            <div class="d-flex flex-column cycle ml-3">
                                <span>per </span>
                                <span>{{ recurring_plan.cycle.replace('ly', '') }}</span>
                            </div>
                        </div>
                        <div class="justify-content-between subscription-mobile">
                            <span class="small">I want to pay</span>
                            <span>{{recurring_plan.name}}</span>
                        </div>
                        <div class="d-flex justify-content-between p-3 mb-3 subscription-box">
                            <div class="d-flex flex-column">
                                <span class="type">Subscription</span>
                                <span class="cycle">Billed {{ recurring_plan.cycle }}</span>
                            </div>
                            <span class="price">{{ recurring_plan.price }}</span>
                        </div>
                        <div class="recurring-expires mb-2">
                            <span>Expires on: </span>{{ expires_at }}
                        </div>
                        <div v-if="recurring_plan.status === 'active'" class="recurring-status">
                            <span>Status: </span> <span class="ml-2 status">{{ recurring_plan.status }}</span>
                        </div>
                    </div>

                </div>

                <div class="main-body">
                    <div class="email-methods">
                        <div class="recurring-email">

                            <div class="input-container">
                                <span class="mr-3">Email</span>
                                <input
                                    v-model="recurring_plan.customer_email"
                                    readonly
                                    type="email">
                            </div>

                        </div>

                        <div
                            id="payment-methods mb-3"
                            class="payment-methods">
                            <div class="recurring-payment w-100">

                                <div class="input-container">
                                    <span class="mr-3">Payment method</span>
                                    <img v-for="card in cardBrands" :src="card" alt="" width="36"
                                         class="payment-brands mx-1">
                                    <select v-model="selected_payment" class="float-right w-25"
                                            :disabled="recurring_plan.status === 'active'">
                                        <option v-for="method in recurring_plan.payment_methods" :value="method">
                                            {{ method.toUpperCase() }}
                                        </option>
                                    </select>
                                </div>

                            </div>
                            <template v-if="recurring_plan.status !== 'active'">
                                <div v-if="selected_payment === 'card'" class="w-100 mt-4">
                                    <subscription
                                        :backColor="currentThemeColors.buttonBack"
                                        :foreColor="currentThemeColors.buttonFore">
                                    </subscription>
                                </div>
                                <div v-else-if="selected_payment === 'giro'" class="mt-4">
                                    <p>Bill Reference: <span
                                        class="font-weight-bold">{{ recurring_plan.dbs_dda_reference }}</span>
                                    </p>
                                    <p>Add HitPay as GIRO billing organisation by following steps below</p>
                                    <ol class="small text-muted mb-0">
                                        <li>Log in to your DBS/ POSB Internet Banking Account on the web</li>
                                        <li>Click on Pay > Add GIRO Arrangement</li>
                                        <li>Under Billing Organisation, Select HitPay</li>
                                        <li>Add <span
                                            class="font-weight-bold">{{ recurring_plan.dbs_dda_reference }}</span>
                                            as Bill Reference
                                        </li>
                                        <li>Enter 0 under Payment Limit</li>
                                        <li>Click on Next and hit Submit</li>
                                    </ol>
                                </div>
                            </template>
                            <template v-else-if="recurring_plan.payment_provider === 'dbs_sg'">
                                <div class="card-body py-4">
                                    Bill Reference: <span
                                    class="font-weight-bold">{{ recurring_plan.dbs_dda_reference }}</span>
                                </div>
                            </template>
                            <template v-else>
                                <div
                                    v-if="recurring_plan.status === 'active' && recurring_plan.payment_provider_payment_method_id"
                                    class="mt-4 w-100">
                                    <div class="small alert alert-info rounded-0 border-left-0 border-right-0 mb-0">
                                        <i class="fa fa-exclamation-triangle mr-1"></i> A card has been attached to this
                                        recurring plan, but you still can update the card.
                                    </div>
                                </div>
                                <div class="w-100 mt-4">
                                    <subscription
                                        :backColor="currentThemeColors.buttonBack"
                                        :foreColor="currentThemeColors.buttonFore">
                                    </subscription>
                                </div>
                            </template>

                        </div>
                        <div v-if="!selected_payment" class="select-payment-text font-weight-medium mt-5 mb-2">
                            <span>Select your payment method</span>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>
<script>
import GetTextColor from '../mixins/GetTextColor'

export default {
    mixins: [
        GetTextColor
    ],
    props: {
        customisation: {
            type: Object,
            required: true
        },
        business: {
            type: Object,
            required: true
        },
        business_image: {
            type: String,
            default: ''
        },
        recurring_plan: {
            type: Object,
            required: true
        },
        expires_at: {
            type: String,
            default: ''
        },
    },
    data() {
        return {
            themeColors: {
                hitpay: {
                    leftPanelBack: '#011B5F',
                    leftPanelFore: 'white',
                    leftPanelFore2: 'white',
                    buttonBack: '#011B5F',
                    buttonFore: 'white'
                },
                light: {
                    leftPanelBack: 'white',
                    leftPanelFore: 'black',
                    leftPanelFore2: '#545454',
                    buttonBack: '#011B5F',
                    buttonFore: 'white'
                },
                custom: {
                    leftPanelBack: this.customisation.tint_color,
                    leftPanelFore: this.getTextColor(this.customisation.tint_color),
                    buttonBack: this.customisation.tint_color,
                    buttonFore: this.getTextColor(this.customisation.tint_color)
                }
            },
            selected_payment: 'card',
            cardBrands: [
                '/icons/payment-brands/visa-small.png',
                '/icons/payment-brands/master.svg',
                '/icons/payment-brands/amex.svg',
            ],

        }
    },
    computed: {
        safeTheme() {
            let theme = this.customisation.theme

            // fail-safe for incorrect theme name
            if (!this.themeColors[theme]) {
                theme = 'hitpay'
            }

            return theme
        },
        leftPanelStyles() {
            return {
                'background-color': this.currentThemeColors.leftPanelBack,
                color: this.currentThemeColors.leftPanelFore,
                fill: this.currentThemeColors.leftPanelFore
            }
        },
        currentThemeColors() {
            return this.themeColors[this.safeTheme]
        },
    }
}
</script>
<style lang="scss">

$breakpoint: 915px;

.left-panel {

    .recurring-description {
        span {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
        }
    }

    .recurring-price {
        .price {
            font-size: 36px;
        }

        .cycle {
            color: rgba(255, 255, 255, 0.8);
        }
    }
    .subscription-mobile{
        display: none;
    }

    @media screen and (max-width: $breakpoint) {
        .left-recurring-main{
            display: flex;
            flex-direction: column-reverse;
        }
        .subscription-mobile{
            display: flex;
            .small{
                font-size: 13px;
            }
        }
        .recurring-description{
            margin-bottom: unset!important;
        }
        .recurring-subscribe, .business-image, .subscription-box, .recurring-expires, .recurring-status {
            display: none !important;
        }
        .recurring-price {
            margin-bottom: 0.25rem !important;

            .price {
                font-size: 32px;
            }
            .cycle{
                flex-direction: row!important;
                align-self: center;
                span{
                    margin-right: 3px;
                    margin-top: 10px;
                    font-size: 13px;
                }
            }
        }
    }

    @media screen and (min-width: $breakpoint) {
        padding: 80px !important;

        .business-image {
            img {
                border-radius: 10%;
            }
        }
        .recurring-subscribe {
            span {
                font-size: 22px;
            }
        }
        .subscription-box {
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.05);

            .cycle {
                font-size: 14px;
                color: rgba(255, 255, 255, 0.8);
            }

            .price {
                font-size: 22px;
                align-self: center;
            }
        }
        .recurring-expires {
            font-size: 14px;

            span {
                color: rgba(255, 255, 255, 0.8);
            }
        }
        .recurring-status {
            font-size: 14px;

            span {
                color: rgba(255, 255, 255, 0.8);
            }

            .status {
                display: inline-block;
                background: #DCF9E8;
                border-radius: 20px;
                color: #0F7041;
                font-size: 14px;
                padding: 5px 15px;

                &:first-letter {
                    text-transform: capitalize;
                }
            }
        }
    }
}

.main-body {

    padding-top: 80px !important;

    @media screen and (max-width: $breakpoint) {
        padding-top: 30px !important;
    }

    .email-methods {
        @media screen and (max-width: $breakpoint) {
            width: 100%;
        }
    }

    .recurring-email {

        margin-bottom: 3rem;

        @media screen and (max-width: $breakpoint) {
            margin-bottom: 1.5rem;
        }

        .input-container {
            width: 100%;
            padding-bottom: 15px;
            border-bottom: solid 1px #979797;

            input {
                border: 0;
                color: #7E8294;;
                font-size: 14px;

                &:focus {
                    border: 0;
                    outline: none;
                }
            }
        }
    }

    .recurring-payment {

        .input-container {
            width: 100%;
            padding-bottom: 15px;
            border-bottom: solid 1px #979797;

            span {
                margin-left: unset;
            }

            select {
                border: 0;
                color: #7E8294;;
                font-size: 14px;

                &:focus {
                    border: 0;
                    outline: none;
                }
            }

            .payment-brands {
                border: 1px solid #00000026;
                border-radius: 3px;

                @media screen and (max-width: $breakpoint) {
                    display: none;
                }
            }
        }
    }
}

.select-payment-text {
    text-align: center;
    font-size: 14px;
    color: #9B9B9B;
    height: 80px;
}
</style>
