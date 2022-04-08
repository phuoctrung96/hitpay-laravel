<template>
    <div>
        <div>
            <template v-if="charges.charge_status === 'succeeded'">
                <div class="checkout-success col-md-12">
                    <div class="align-self-center text-center">
                        <div class="bg-white text-center mt-5 mb-5">
                            <p><i class="fas fa-spinner fa-spin fa-5x text-primary"></i></p>
                            <h5>Please wait...</h5>
                        </div>
                    </div>
                </div>
            </template>

            <template v-else-if="isTimedOut">
                <div class="checkout-success col-md-12">
                    <div class="align-self-center text-center">
                        <div class="bg-white text-center mb-5">
                            <p class="mb-3"><img src="/hitpay/logo-000036.png" height="50" alt="HitPay"></p>
                            <p><img :src="loadImage('icons/sentiment.png')" height="100" alt=""></p>
                            <h5>Checkout page timed out. Please retry completing order from merchant website and ensure
                                you complete payment within 3 minutes.</h5>
                            <a :href="referer" class="btn btn-success mt-5">Back to Merchant Page</a>
                        </div>
                    </div>
                </div>
            </template>

            <template v-else>
                <transition name="fade-slow">
                    <div
                        v-show="showMobileTopPanel"
                        id="mobile-top-panel"
                        class="mobile-flex"
                        :style="leftPanelStyles">

                        <a
                            :href="referer"
                            @click="recordEvent('payment_status', 'back')">
                            <BackSign/>
                        </a>

                        <div class="d-flex justify-content-center flex-grow-1">
                          <span
                              class="sign font-weight-light"
                              :class="{ error: shakingErrors.amount }">{{ symbol }}</span>

                                        <input
                                            v-model.lazy="checkout.charge_amount"
                                            v-money="money"
                                            :readonly="!show_amount_input"
                                            :style="amountStyleMobile"
                                            class="font-weight-medium"/>

                                        <span
                                            class="currency"
                                            :style="leftPanelFore2Styles">
                            {{ currency }}
                          </span>

                          <!-- Hidden div to accurately measure size -->
                          <div
                              ref="measureAmountMobile"
                              class="input-amount-mobile hidden">
                              {{ checkout.charge_amount }}
                          </div>
                        </div>
                    </div>
                </transition>

                <div class="checkout d-flex flex-column">
                    <div
                        v-if="showTestPaymentNotice"
                        class="test-payment-notice d-flex justify-content-between align-items-center">
                        <span><b>TEST PAYMENT:</b> For PayNow scan the QR code using any QR code reader (Do not use banking App). For test cards use this <a
                            href="https://stripe.com/docs/testing" target="_blank">link</a>. To test webhook payload use <a
                            href="https://webhook.site" target="_blank">webhook.site</a>.</span>
                        <i
                            class="fas fa-times ml-2"
                            role="button"
                            @click="showTestPaymentNotice = false"/>
                    </div>

                    <div class="checkout-main d-flex flex-grow-1">
                        <div
                            class="left-panel"
                            :class="safeTheme"
                            :style="leftPanelStyles">

                            <div
                                v-if="referer"
                                class="back"
                                :class="{ 'merchant-image': merchant_image }">

                                <a
                                    :href="referer"
                                    @mouseover="showBackToMerchant = true"
                                    @mouseleave="showBackToMerchant = false"
                                    @click="recordEvent('payment_status', 'back')">

                                    <BackSign/>

                                    <transition name="fade">
                                        <div
                                            v-show="showBackToMerchant"
                                            class="text-container">
                                            <span class="ml-3">Back</span>
                                            <div
                                                v-if="merchant_image"
                                                class="merchant-image">
                                                <img height="26" :src="merchant_image"/>
                                            </div>
                                            <span v-else>&nbsp;to merchant</span>
                                        </div>
                                    </transition>
                                </a>
                            </div>

                            <div class="left-main">
                                <div class="title-amount-name">
                                    <div class="title-amount">
                                        <span
                                            class="hint"
                                            :style="leftPanelFore2Styles">
                                          {{ show_amount_input ? 'I want to pay' : 'You are paying' }}
                                        </span>

                                        <div class="amount-currency">
                                            <div
                                                class="amount"
                                                :class="{ shaking: shakingErrors.amount }">

                                            <span
                                                class="sign font-weight-light"
                                                :class="{ error: shakingErrors.amount }">{{ symbol }}</span>

                                                                  <input
                                                                      v-model.lazy="checkout.charge_amount"
                                                                      v-money="money"
                                                                      :readonly="!show_amount_input"
                                                                      :style="amountStyle"
                                                                      class="input-amount font-weight-medium"
                                                                      :class="{ error: shakingErrors.amount }"/>

                                                                  <!-- Hidden div to accurately measure size -->
                                                                  <div
                                                                      ref="measureAmount"
                                                                      class="input-amount hidden">
                                                                      {{ checkout.charge_amount }}
                                                                  </div>
                                                              </div>

                                                              <span
                                                                  class="currency"
                                                                  :style="leftPanelFore2Styles">
                                            {{ currency }}
                                        </span>
                                        </div>
                                    </div>

                                    <div class="name">{{ business.name }}</div>
                                </div>

                                <input
                                    v-model="checkout.description"
                                    type="text"
                                    class="description"
                                    :class="descriptionClass"
                                    :style="descriptionStyle"
                                    :placeholder="descriptionPlaceholder"
                                    @change="updatePaymentIntent"/>

                                <!-- Hidden div to accurately measure size -->
                                <div
                                    ref="measureDescription"
                                    class="description hidden">
                                    {{ checkout.description || descriptionPlaceholder }}
                                </div>
                            </div>

                            <div class="desktop-flex align-items-center justify-content-center">
                                <a
                                    class="copyright desktop-flex align-items-center"
                                    href="https://hitpayapp.com"
                                    target="_blank">
                                    Powered by
                                    <div
                                        class="v-divider mx-2"
                                        :style="copyrightStyles"/>

                                    <svg
                                        height="16"
                                        viewBox="0 0 576 144">
                                        <use xlink:href='/images/hitpay.svg#hitpay'></use>
                                    </svg>
                                </a>

                                <div
                                    class="v-divider mx-2"
                                    :style="copyrightStyles"/>

                                <a href="https://www.hitpayapp.com/privacypolicy" target="_blank"
                                   class="copyright desktop-flex align-items-center">Privacy</a>&nbsp;
                                <a href="https://www.hitpayapp.com/termsofservice" target="_blank"
                                   class="copyright desktop-flex align-items-center">Terms</a>
                            </div>
                        </div>

                        <div class="main-body">
                            <transition name="slide-y">
                                <div
                                    v-show="charges.chosen_method === 'card' && psElementMounted"
                                    class="apple-pay w-100 text-center">

                                    <div class="inner">
                                        <div id="payment-request-button"/>
                                        <!--<div style="height: 56px; min-height: 56px; width: 100%; background-color: red">ABC</div>-->
                                        <CheckoutDivider style="min-height: 18px" text="Or"/>
                                    </div>
                                </div>
                            </transition>

                            <div class="email-methods">
                                <div class="email">
                                    <span>Email</span>

                                    <div class="input-container">
                                        <input
                                            v-model="checkout.email"
                                            :readonly="!show_email_input"
                                            type="email"
                                            size="30"
                                            placeholder="Enter your email address"/>
                                    </div>

                                    <div
                                        class="input-error"
                                        :class="{ shaking: shakingErrors.email }">
                                        <span v-if="shakingErrors.email">{{ errors.email }}</span>
                                    </div>
                                </div>

                                <div
                                    id="payment-methods"
                                    class="payment-methods">
                                    <span>Select Payment Method</span>

                                    <div
                                        class="container flex-wrap"
                                        :class="{ 'single-method': orderedMethods.length <= 1 }">
                                        <PaymentMethod
                                            v-for="(m, index) in orderedMethods"
                                            :key="'payment-method-'+index"
                                            :imageUrl="paymentMethodImages[m]"
                                            :selected="charges.chosen_method === m"
                                            :disabled="changingMethod || qrGeneration"
                                            :cashback="getCashback(m)"
                                            :campaign_rule="!cashback.length ? getCampaign(m) : null"
                                            :setHeight="paymentMethodSetHeight.includes(m)"
                                            @click="changeMethod(m)"/>
                                    </div>

                                    <div
                                        v-if="!dataOk && helperText"
                                        class="helper-text font-weight-medium mt-5 mb-2">
                                        <ul>
                                            <li v-for="(ht, index) in helperText" :key="index">
                                                {{ ht }}
                                            </li>
                                        </ul>
                                    </div>

                                    <div
                                        v-show="charges.chosen_method === 'paynow_online'"
                                        key="options-paynow"
                                        class="options">

                                        <div
                                            v-if="qrGeneration"
                                            class="qr-spinner bg-white text-center mb-3">
                                            <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                                        </div>

                                        <div
                                            v-show="!qrGeneration && in_progress && charges.charge_status !== 'succeeded'"
                                            class="qr-code mb-3 text-center"
                                            :class="{ samsung: isSamsungBrowser }">
                                            <img ref="paynowQrCode"/>
                                        </div>

                                        <div class="desktop">
                                            <div class="d-flex flex-column">
                                                <div class="d-flex align-items-end justify-content-center">
                                                    <span class="text-1 line-height-1">Scan above QR code image using your internet banking app</span>
                                                    <!--<img src="/images/apple-phone.png" class="ml-1" height="20"/>-->
                                                </div>

                                                <div class="d-flex justify-content-center mt-4 mb-2">
                                                    <img
                                                        v-for="(image, index) in bankImages"
                                                        :key="`bank-image-${index}`"
                                                        :src="image"
                                                        class="bank-image"/>
                                                </div>

                                                <span class="text-2 mt-2 mb-4">Payment will be made to <span
                                                    class="text-2 font-weight-medium">"HITPAY PAYMENTS - CUSTOMERS’ ACCOUNT"</span> on behalf of "{{
                                                        business.name
                                                    }}"</span>
                                            </div>
                                        </div>

                                        <ol class="mobile mt-2">
                                            <template v-if="payNowBank === 'dbs'">
                                                <li>Take a screenshot of the above QR code</li>
                                                <li>Login to DBS DigiBank App</li>
                                                <li>Select "Pay & Transfer" bottom tab</li>
                                                <li>Select "Scan & Pay" > "Photo Library"</li>
                                                <li>Select the QR Image</li>
                                                <li>Finish Payment and return back to this page</li>
                                            </template>

                                            <template v-else-if="payNowBank === 'paylah'">
                                                <li>Take a screenshot of the above QR code</li>
                                                <li>Login to PayLah!</li>
                                                <li>Select "Scan"</li>
                                                <li>Select "Album" on the top right</li>
                                                <li>Select the QR Image</li>
                                                <li>Finish Payment and return back to this page</li>
                                            </template>

                                            <template v-else>
                                                <template v-if="getMobileOperatingSystem === 'ios'">
                                                    <li>Press and hold the above QR code</li>
                                                    <li>Click on “Add to Photos”</li>
                                                </template>

                                                <template v-else>
                                                    <li>Take a screenshot of the above PayNow QR</li>
                                                </template>

                                                <li>Open your banking app & upload the above image</li>
                                                <li>Complete the payment and return back to this page</li>
                                            </template>
                                        </ol>

                                        <div class="mobile-flex justify-content-center align-items-center mb-3">
                                            <img
                                                v-for="(image, index) in bankImagesMobile"
                                                :key="`mobile-bank-image-${index}`"
                                                :src="image"
                                                class="bank-image"/>

                                            <span
                                                data-toggle="tooltip"
                                                data-placement="top"
                                                data-html="true"
                                                data-trigger="click"
                                                :title="bankImagesOther"
                                                class="other-banks font-weight-light mb-1 ml-2">& Other banks</span>
                                        </div>
                                    </div>

                                    <div
                                        v-show="charges.chosen_method === 'card'"
                                        key="options-card"
                                        class="options">
                                        <div
                                            v-show="errorMessage"
                                            class="alert alert-danger">
                                            {{ errorMessage }}
                                        </div>

                                        <div id="card-element" class="form-control bg-white p-2"></div>

                                        <div class="desktop-flex card-logos">
                                            <div
                                                v-for="(logo, index) in cardBrands"
                                                :key="index">
                                                <img :src="logo"/>
                                            </div>
                                        </div>

                                        <template v-if="in_progress && charges.charge_status !== 'succeeded'">
                                            <p class="mb-0 mt-2 text-center font-weight-bold">Please wait while we
                                                process your payment.</p>

                                            <div class="bg-white text-center mt-2 mb-5">
                                                <p><i class="fas fa-spinner fa-spin fa-3x text-primary"></i></p>
                                            </div>
                                        </template>
                                    </div>

                                    <div
                                        v-show="charges.chosen_method === 'wechat'"
                                        key="options-wechat"
                                        class="options">

                                        <div
                                            v-if="qrGeneration"
                                            class="qr-spinner bg-white text-center mb-3">
                                            <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                                        </div>

                                        <div
                                            v-show="!qrGeneration && in_progress && charges.charge_status !== 'succeeded'"
                                            class="qr-code mb-3 text-center"
                                            :class="{ samsung: isSamsungBrowser }">
                                            <img ref="wechatQrCode"/>
                                        </div>

                                        <span class="text-1">Scan above WeChatPay QR code.</span>
                                        <span
                                            v-if="in_progress && charges.charge_status !== 'succeeded'"
                                            class="text-2 my-2 font-weight-medium">Awaiting payment. Please do not close this window until you receive payment confirmation.</span>
                                    </div>

                                    <div
                                        v-show="charges.chosen_method === 'alipay'"
                                        class="options"
                                        key="options-alipay">

                                        <h4 class="mb-3 text-center font-weight-bold">Alipay</h4>
                                        <p class="mb-1 mt-2 text-center font-weight-light">Scan AliPay QR or
                                            authenticate using your AliPay credentials. Clicking the below button will
                                            redirect you to AliPay payment page.</p>
                                        <p class="mt-2 text-center font-weight-light">Please do not close this window
                                            until you receive payment confirmation.</p>

                                        <div v-if="in_progress" class="bg-white text-center mt-2 mb-5">
                                            <p><i class="fas fa-spinner fa-spin fa-3x text-primary"></i></p>
                                        </div>
                                    </div>

                                    <div
                                        v-show="charges.chosen_method === 'grabpay' || charges.chosen_method === 'grabpay_direct' || charges.chosen_method === 'grabpay_paylater'"
                                        class="options"
                                        key="options-grabpay">

                                        <h4 class="mb-3 text-center font-weight-bold">GrabPay</h4>
                                        <p class="mt-2 text-center font-weight-light">Please do not close this window
                                            until you receive payment confirmation.</p>

                                        <div v-if="in_progress" class="bg-white text-center mt-2 mb-5">
                                            <p><i class="fas fa-spinner fa-spin fa-3x text-primary"></i></p>
                                        </div>
                                    </div>

                                    <div
                                      v-show="charges.chosen_method === 'shopee_pay'"
                                      key="options-shopee"
                                      class="options">
                                      <div
                                        v-if="qrGeneration"
                                        class="qr-spinner bg-white text-center mb-3">
                                        <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                                      </div>

                                      <img
                                        v-if="errorMessage"
                                        :src="loadImage('icons/sentiment.png')" height="100" alt="">

                                      <template v-else>
                                        <div
                                            v-show="!qrGeneration && in_progress && charges.charge_status !== 'succeeded'"
                                            class="qr-code mb-3 text-center"
                                            :class="{ samsung: isSamsungBrowser }">
                                            <img ref="shopeeQrCode"/>
                                        </div>

                                        <span class="text-1">Scan above Shopee Pay QR code.</span>
                                      </template>

                                      <span
                                        v-if="in_progress && charges.charge_status !== 'succeeded'"
                                        class="text-2 my-2 font-weight-medium">Awaiting payment. Please do not close this window until you receive payment confirmation.</span>
                                    </div>

                                    <div
                                      v-show="charges.chosen_method === 'hoolah'"
                                      class="options"
                                      key="options-hoolah">

                                      <h4 class="mb-3 text-center font-weight-bold">Hoolah</h4>
                                      <p class="mt-2 text-center font-weight-light">Please do not close this window until you receive payment confirmation.</p>

                                      <div v-if="in_progress" class="bg-white text-center mt-2 mb-5">
                                        <p><i class="fas fa-spinner fa-spin fa-3x text-primary"></i></p>
                                      </div>
                                    </div>

                                    <CheckoutButton
                                        :title="payButtonTitle"
                                        :dots="in_progress"
                                        :backColor="currentThemeColors.buttonBack"
                                        :foreColor="currentThemeColors.buttonFore"
                                        class="checkout-button"
                                        :disabled="payButtonDisabled"
                                        @click="payButtonOnClick()"
                                        @disabledClick="payButtonOnDisabledClick()"
                                    />

                                    <span
                                        v-if="charges.chosen_method === 'paynow_online'"
                                        class="mobile text-2-mobile">Payment will be made to <span
                                        class="text-2-mobile font-weight-medium">“HITPAY PAYMENTS - CUSTOMERS’ ACCOUNT</span> on behalf of “{{
                                            business.name
                                        }}”</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <DeepLinkPanel
                    v-model="deepLinkVisible"
                    :transactionId="charges.charge_object.id"
                    :qrString="qrcode_data"
                    :cashbackFor="campaign_rule ? 'ocbc' : ''"
                    :cashbackAmount="campaign_rule ? campaignCashbackAmount : ''"
                    :payNowBank.sync="payNowBank"
                    @redirect="onDeeplinkRedirect"/>
            </template>
        </div>
    </div>
</template>

<script>
import numberMixin from "../../mixins/numberMixin"
import {VMoney} from 'v-money'
import CheckoutButton from './CheckoutButton'
import CheckoutDivider from './CheckoutDivider'
import PaymentMethod from './PaymentMethod'
import BackSign from './BackSign'
import GetTextColor from '../../mixins/GetTextColor'
import DeepLinkPanel from './DeepLinkPanel'
import QRCodeNew from 'qrcode'

export default {
    components: {
        CheckoutButton,
        CheckoutDivider,
        PaymentMethod,
        BackSign,
        DeepLinkPanel
    },
    mixins: [
        numberMixin,
        GetTextColor
    ],
    directives: {
        money: VMoney
    },
    props: {
        charge: {
            type: Object,
            required: true
        },
        business: {
            type: Object,
            required: true
        },
        customisation: {
            type: Object,
            required: true
        },
        countries: {
            required: true
        },
        methods: {
            required: true
        },
        amount: {
            required: true
        },
        data: {
            required: true
        },
        referer: {
            required: false
        },
        default_url_completed: {
            required: true
        },
        merchant_image: {
            type: String,
            default: ''
        },
        without_payment_request: false,
        cashback_for: {
            type: String,
            default: ''
        },
        cashback_amount: {
            type: String,
            default: ''
        },
        symbol: {
            type: String,
            required: true,
            default: '$'
        },
        allow_deep_link_panel: {
            type: Boolean,
            default: false
        },
        mode: {
            type: String,
            default: 'other'
        },
        cashback: {
            type: Array,
            default() {
                return []
            }
        },
        campaign_rule: {
            type: Object,
            default() {
                return null
            }
        },
        default_method: {
            type: String,
            default: ''
        },
        show_test_payment: {
            type: Boolean,
            default: false
        },
        zero_decimal: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            isTimedOut: false,
            qrcode_data: null,
            disabled_payment_method_buttons: true,
            qrcode: null,
            is_processing: false,
            currency: '',
            existing_charge: null,
            in_progress: false,
            base_url: null,
            base_url_business: null,
            show_payment_request_button: true,
            show_amount_input: false,
            show_email_input: true,
            money: {
                decimal: '.',
                thousands: ',',
                prefix: '',
                suffix: '',
                precision: this.zero_decimal ? 0 : 2,
                masked: false /* doesn't work with directive */
            },
            errors: {
                email: null,
                amount: null
            },
            shakingErrors: {
                email: false,
                amount: false
            },
            checkout: {
                country: null,
                email: null,
                description: null,
                charge_amount: 0
            },
            cards: {
                card: null,
                elements: null,
                status: null,
                stripe: null,
                payment_request: null,
            },
            errorMessage: '',
            charges: {
                charge_object: {},
                chosen_method: null,
                charge_status: null,
                methods: {
                    alipay: false,
                    grabpay: false,
                    card: false,
                    card_present: false,
                    wechat: false,
                }
            },
            terminals: {
                connected: null,
                stripe: null,
                status: null,
            },
            // Order of fields is important, methods will be shown in this order
            paymentMethodImages: {
                paynow_online: 'paynow-new.svg',
                card: 'card-new2.svg',
                alipay: 'alipay-new.svg',
                //wechat: 'weechat-new3.svg',
                grabpay: 'grabpay3.png',
                grabpay_direct: 'grabpay3.png',
                grabpay_paylater: 'grabpay_paylater2.png',
                shopee_pay: 'shopee.png',
                hoolah: 'hoolah.png',
                zip: 'zip.png'
            },
            paymentMethodSetHeight: [
              'grabpay',
              'grabpay_direct',
              'grabpay_paylater'
            ],
            qrGeneration: false,
            qrSize: 227,
            showBackToMerchant: false,
            descriptionPlaceholder: 'Enter Description',
            descriptionWidth: 0,
            amountWidth: 0,
            amountWidthMobile: 0,
            // becomes true if all data a valid at least one time
            dataOk: false,
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
            psElementMounted: false,
            cardElementMounted: false,
            emailEnterTimer: false,
            cardBrands: [
                '/icons/payment-brands/visa-small.png',
                '/icons/payment-brands/master.svg',
                '/icons/payment-brands/amex.svg',
                '/icons/payment-brands/unionpay.svg'
            ],
            bankImages: [
                '/icons/payment-banks/banks-2.png',
                '/icons/payment-banks/banks-dbs.png',
                '/icons/payment-banks/banks-gpay.png',
                '/icons/payment-banks/banks-ocbc-pay-anyone.png',
                '/icons/payment-banks/banks-uob.png',
                '/icons/payment-banks/banks-singapore.png',
                '/icons/payment-banks/banks-city.png',
                '/icons/payment-banks/banks-hsbc.png',
                '/icons/payment-banks/banks-maybank.png',
                '/icons/payment-banks/banks-china.png'
            ],
            scrollPos: 0,
            deepLinkVisible: false,
            deepLinkRedirect: false,
            timeoutValue: 0,
            timeoutTimer: null,
            lazyPaynowQR: false,
            changingMethod: false,
            suppressBeforeUnload: false,
            payNowBank: '', // bank selected via deeplink, affects text under QR
            showTestPaymentNotice: this.show_test_payment,
            hoolahRedirect: '',
            shopeeQrUrl: '',
            orderStatusPoll: null
        }
    },
    async created() {
        this.base_url = this.getDomain('v1/business/' + this.business.id + '/plugin/', 'api')
        this.base_url_business = this.getDomain('v1/plugin/business/' + this.business.id + '/', 'api')

        if (this.mode === 'payment-request') {
            // Check actual charge.status
            // If user loads checkout page from browser cache (using Back button for ex)
            // charge status may be incorrect

            const res = await axios.get(`${this.base_url}charge/${this.charge.id}/charge-completed`)

            if (res.data.completed) {
                this.alreadyPaid()
            }
        }
    },
    mounted() {
        // timeout is in msec
        this.timeoutValue = parseInt(this.getPaymentGatewayValue('timeout')) * 1000

        this.provider_callback_url = this.getDomain('payment-request/' + this.charge.id + '/callback', 'securecheckout')
        this.provider_callback_url_without_payment_request = this.getDomain(
            'payment-gateway/' + this.data.plugin_provider + '/callback/' + this.business.id,
            'securecheckout'
        )
        this.currency = this.data.currency.toUpperCase()
        this.charges.charge_object = this.charge

        // set email
        this.checkout.email = this.data.customer_email

        // if email provided, disable its edit in payment request mode
        if (this.mode === 'payment-request' && this.checkout.email) {
            this.show_email_input = false
        }

        this.checkout.description = this.data.description

        this.checkout.charge_amount = this.amount
        this.show_amount_input = this.checkout.charge_amount === '0.00' || this.checkout.charge_amount === 0 ? true : false

        // Only select first method if there are no errors in data
        this.charges.chosen_method = !this.checkAmount() && !this.checkEmail() && !(this.isMobileOS && this.orderedMethods.length > 1)
            ? this.defaultMethod
            : ''

        this.terminals.stripe = StripeTerminal.create({
            onFetchConnectionToken: async () => {
                return axios.post(this.base_url_business + 'token').then(({data}) => data.secret)
            },
            onUnexpectedReaderDisconnect: () => {
                try {
                    this.terminals.stripe.disconnectReader();
                } catch (e) {
                    //
                }

                // todo fix this, use modal to display
                this.terminals.connected = null
                this.terminal_status = 'The reader is disconnected.'
            },
        })

        this.cards.stripe = Stripe(StripePublishableKey, {
            betas: [
                'payment_intent_beta_3',
                'grabpay_pm_beta_1'
            ],
        })

        this.cards.elements = this.cards.stripe.elements();

        if (this.methods.includes('card') && this.show_amount_input === false) {
            this.createStripePaymentRequest(this.getStripeAmount())
        } else {
            this.show_payment_request_button = false
        }

        window.onbeforeunload = () => {
            if (this.charges.charge_status === 'succeeded' || this.deepLinkRedirect || this.suppressBeforeUnload) {
                return null
            } else {
                return true
            }
        }

        if (!this.show_amount_input) {
            this.disabled_payment_method_buttons = false
        }

        if (this.charges.chosen_method) {
            this.changeMethod(this.charges.chosen_method, false)
        }

        $('body').tooltip({selector: '[data-toggle=tooltip]'})

        window.document.addEventListener('scroll', this.handleScroll);
    },
    beforeDestroy() {
        this.clearTimeoutTimer()
    },
    computed: {
        amountStyle() {
            return {
                width: this.amountWidth + 'px',
                color: this.shakingErrors.amount ? 'red' : 'inherit'
            }
        },
        amountStyleMobile() {
            return {
                width: this.amountWidthMobile + 'px',
                color: this.shakingErrors.amount ? 'red' : 'inherit'
            }
        },
        descriptionStyle() {
            return {
                ...this.leftPanelFore2Styles,
                width: this.descriptionWidth + 'px'
            }
        },
        payButtonTitle () {
          return this.in_progress
            ? 'Waiting for payment'
            : this.charges.chosen_method === 'alipay'
              ? 'Click to pay with AliPay'
              : this.charges.chosen_method === 'grabpay'
                ? 'Click to pay with GrabPay'
                : this.charges.chosen_method === 'hoolah'
                  ? 'Click to pay with Hoolah'
                  : `Pay ${this.symbol}${this.checkout.charge_amount}`
        },
        payButtonDisabled() {
            switch (this.charges.chosen_method) {
                case 'paynow_online':
                case 'wechat': {
                    return false
                }
                case 'card':
                case 'alipay':
                case 'grabpay':
                case 'grabpay_direct':
                case 'grabpay_paylater':
                case 'hoolah':
                case 'zip': {
                    return this.in_progress || this.hasErrors
                }
                default: {
                    return true
                }
            }
        },
        orderedMethods() {
            // First detect list of allowed methods, according to rules
            let available = this.methods

            if (this.customisation.method_rules) {
                if (this.customisation.method_rules.device && this.customisation.method_rules.device.length > 0) {
                    const deviceMethod = this.customisation.method_rules.device[0]

                    if (deviceMethod && deviceMethod.enabled &&
                        ((deviceMethod.options.type === 'mobile' && this.isMobileOS) ||
                            (deviceMethod.options.type === 'desktop' && !this.isMobileOS))) {
                        available = deviceMethod.methods
                    }
                }

                if (this.customisation.method_rules.amount && this.customisation.method_rules.amount.length > 0) {
                    for (let i = 0; i < this.customisation.method_rules.amount.length; i++) {
                        const amountMethod = this.customisation.method_rules.amount[i]

                        const filteredAmount = String(this.checkout.charge_amount).replace(',', '')

                        if (amountMethod && amountMethod.enabled && !this.show_amount_input &&
                            Number(filteredAmount) >= Number(amountMethod.options.min) &&
                            (!amountMethod.options.max || Number(filteredAmount) <= Number(amountMethod.options.max))) {
                            available = amountMethod.methods
                            break
                        }
                    }
                }
            }

            let res = Object.keys(this.paymentMethodImages).filter(m => available.includes(m))

            // Sort items according to payment order
            if (this.customisation.payment_order) {
                res.sort((a, b) => {
                    let iA = this.customisation.payment_order.indexOf(a)
                    let iB = this.customisation.payment_order.indexOf(b)

                    // Move items that are not in payment_order into the end
                    iA = iA < 0 ? 999 : iA
                    iB = iB < 0 ? 999 : iB

                    return iA - iB
                })
            }

            return res
        },
        hasErrors() {
            return Boolean(this.errors.amount) || Boolean(this.errors.email)
        },
        helperText() {
            let res = []

            if (this.errors.amount) {
                res.push('Enter a valid amount')
            }

            if (this.errors.email) {
                res.push('Enter a valid email')
            }

            if (!this.charges.chosen_method) {
                res.push('Select your payment method')
            }

            return res.length > 0
                ? res
                : false
        },
        currentThemeColors() {
            return this.themeColors[this.safeTheme]
        },
        leftPanelStyles() {
            return {
                'background-color': this.currentThemeColors.leftPanelBack,
                color: this.currentThemeColors.leftPanelFore,
                fill: this.currentThemeColors.leftPanelFore
            }
        },
        leftPanelFore2Styles() {
            return {
                color: this.currentThemeColors.leftPanelFore2
            }
        },
        copyrightStyles() {
            return {
                'background-color': this.currentThemeColors.leftPanelFore2
            }
        },
        safeTheme() {
            let theme = this.customisation.theme

            // fail-safe for incorrect theme name
            if (!this.themeColors[theme]) {
                theme = 'hitpay'
            }

            return theme
        },
        descriptionClass() {
            return this.safeTheme === 'custom'
                ? this.getTextColor(this.currentThemeColors.leftPanelBack) === 'black'
                    ? 'hitpay'
                    : 'custom-light'
                : this.safeTheme
        },
        bankImagesMobile() {
            return this.bankImages.slice(0, 5)
        },
        bankImagesOther() {
            return '<span class="text-left">Other banks:</span><ul class="mb-0 pl-3"><li>Standard Chartered Bank</li><li>Maybank</li><li>Bank of China</li><li>Citibank</li><li>HSBC</li><li>ICBC</li></ul>'
        },
        showMobileTopPanel() {
            return this.scrollPos > 100 || this.deepLinkVisible
        },
        getMobileOperatingSystem() {
            const userAgent = navigator.userAgent || navigator.vendor || window.opera

            // Windows Phone must come first because its UA also contains "Android"
            if (/windows phone/i.test(userAgent)) {
                return 'windows_phone'
            }

            if (/android/i.test(userAgent)) {
                return 'android'
            }

            // iOS detection from: http://stackoverflow.com/a/9039885/177710
            if (/iPad|iPhone|iPod/.test(userAgent) && !window.MSStream) {
                return 'ios'
            }

            return 'unknown'
        },
        isMobileOS() {
            return this.getMobileOperatingSystem === 'ios' || this.getMobileOperatingSystem === 'android'
        },
        allowDeepLink() {
            return this.allow_deep_link_panel && this.data.plugin_provider !== 'woocommerce' && this.mode !== 'default'
        },
        defaultMethod() {
            return this.default_method && this.orderedMethods.includes(this.default_method)
                ? this.default_method
                : this.orderedMethods[0]
        },
        campaignCashbackAmount(){
            return (this.campaign_rule.cashback_amt_percent > 0 ? this.campaign_rule.cashback_amt_percent  + "%" : '') + (this.campaign_rule.cashback_amt_fixed ?' +$' + this.campaign_rule.cashback_amt_fixed : '');
        },
        isSamsungBrowser () {
          return navigator.userAgent.match(/SAMSUNG|Samsung|SGH-[I|N|T]|GT-[I|N]|SM-[A|N|P|T|Z]|SHV-E|SCH-[I|J|R|S]|SPH-L/i)
        },
        qrMargin () {
          return this.isSamsungBrowser ? 4 : 0
        }
    },
    watch: {
        'checkout.description': {
            immediate: true,
            handler(value) {
                this.$nextTick(() => {
                    if (this.$refs.measureDescription) {
                        this.descriptionWidth = this.$refs.measureDescription.clientWidth
                    }
                })
            }
        },
        'checkout.charge_amount': {
            immediate: true,
            handler(value) {
                this.measureInputs()

                if (this.checkAmount()) {
                    if (this.dataOk) {
                        // Set error if user enters invalid data after valid
                        this.shakingErrors.amount = true
                    }
                } else {
                    this.shakingErrors.amount = false
                }
            }
        },
        'checkout.email': {
            handler(value) {
                // Reset existing email check timer if any
                if (this.emailEnterTimer) {
                    window.clearTimeout(this.emailEnterTimer)
                }

                if (this.checkEmail()) {
                    if (this.dataOk) {
                        // Set error if user enters invalid data after valid
                        this.shakingErrors.email = true
                    }
                } else {
                    this.shakingErrors.email = false

                    // If no payment method was selected
                    if (!this.charges.chosen_method && !(this.isMobileOS && this.orderedMethods.length > 1)) {
                        window.setTimeout(() => {
                            // If email is still OK & payment method is still not selected
                            if (!this.checkEmail() && !this.charges.chosen_method) {
                                this.changeMethod(this.defaultMethod)
                            }
                        }, 3000)
                    }
                }
            }
        },
        deepLinkVisible() {
            if (!this.deepLinkVisible && this.lazyPaynowQR) {
                // generate QR when user hides deepLink panel
                this.generatePayNowQR()
            }
        }
    },
    methods: {
        updatePaymentIntent(){
            axios.post(this.base_url + 'charge/' + this.charges.charge_object.id + '/update-payment-intent', {'description': this.checkout.description});
        },
        hasPaymentMethod(method) {
            return this.methods.includes(method) ? true : false
        },

        async updateStripePaymentRequest() {
            // checkout.charge_amount will always have a value in a floating point format
            const amount = this.getStripeAmount()

            if (this.methods.includes('card')) {
                if (this.show_payment_request_button === false) {
                    this.show_payment_request_button = true;
                    this.createStripePaymentRequest(amount)
                } else if (this.show_amount_input === true) {
                    if (this.cards.payment_request && await this.cards.payment_request.canMakePayment()) {
                        this.cards.payment_request.update({
                            total: {
                                label: 'Checkout Total',
                                amount: amount
                            }
                        })
                    }
                }
            }
        },

        async changeMethod(method, scroll = true) {
            if (this.isValidInput() && !this.changingMethod) {
                this.changingMethod = true

                try {
                    // if user entered valid data and selected a method at least one time
                    this.dataOk = true

                    if (this.mode === 'default') {
                        this.show_amount_input = false
                        this.show_email_input = false
                    }

                    this.charges.chosen_method = method
                    this.errorMessage = null
                    this.in_progress = false

                    if (method === 'paynow_online' && this.isMobileOS) {
                        this.lazyPaynowQR = this.allowDeepLink

                        if (this.charges.chosen_method === 'paynow_online' && this.allowDeepLink) {
                            this.scrollTo('#payment-methods')
                            // Show deep link panel only of this is allowed in .env
                            this.deepLinkVisible = true
                        }
                    } else {
                        this.lazyPaynowQR = false
                    }

                    if (method === 'paynow_online' || method === 'wechat' || method === 'shopee_pay') {
                        await this.pay(scroll)
                    } else {
                        await this.updateStripePaymentRequest();
                    }
                } finally {
                    this.changingMethod = false
                }
            }
        },

        async pay(scroll = true) {
            this.errorMessage = null
            this.qrcode_data = null
            let that = this

            if (scroll) {
                this.scrollTo('#payment-methods')
            }

            if (this.qrcode != null) {
                this.qrcode.clear()
            }

            if (!this.isValidInput()) {
                this.in_progress = false

                return;
            }

            this.disabled_payment_method_buttons = true

            if (this.charges.chosen_method === 'cash') {
                this.in_progress = true

                axios.post(this.base_url + 'charge/' + this.charges.charge_object.id + '/cash').then(({data}) => {
                    this.charges.charge_status = 'succeeded'
                })
            } else {
                if (this.charges.chosen_method === 'card' && this.errorMessage === null && (this.cards.stripe === null || this.cards.card === null)) {
                    return
                }

                this.in_progress = true
                this.qrGeneration = true

                // without setTimeout (even with zero delay) sometimes spinner will not be shown during QR generation
                window.setTimeout(async () => {
                  const {data} = await axios.post(this.base_url + 'charge/' + this.charges.charge_object.id + '/create-payment-intent', {
                      method: this.charges.chosen_method,
                      email: this.checkout.email,
                      description: this.checkout.description,
                      amount: this.checkout.charge_amount
                  })

                  if (data.alreadyPaid) {
                      this.alreadyPaid()
                  } else {
                      this.runTimeoutTimer()

                      this.existing_charge = data

                      await this.updateStripePaymentRequest()

                      if (this.charges.chosen_method === 'card_present') {
                          this.terminals.stripe.collectPaymentMethod(data.payment_intent.client_secret)
                              .then(result => {
                                  if (result.error) {
                                      this.charges.charge_status = this.getTerminalErrorMessage(result.error)
                                  } else {
                                      this.terminals.status = 'paying'
                                      this.terminals.stripe.processPayment(result.paymentIntent).then(result => {
                                          if (result.error) {
                                              this.charges.charge_status = this.getTerminalErrorMessage(result.error)
                                          } else if (result.paymentIntent) {
                                              axios.post(this.base_url + 'charge/payment-intent/' + this.existing_charge.id + '/capture').then(({data}) => {
                                                  this.redirectComplete()
                                              });
                                          }
                                      });
                                  }
                              });
                      } else {
                          this.disabled_payment_method_buttons = false

                          if (this.charges.chosen_method === 'card') {
                              await this.cards.stripe.createPaymentMethod({
                                  type : "card",
                                  card : this.cards.card
                              }).then(async result => {
                                  if (result.error) {
                                      this.errorMessage = result.error.message
                                      this.in_progress = false
                                  } else {
                                      var updatePaymentIntentUrl = `${this.base_url}charge/payment-intent/${this.existing_charge.id}/confirm`;

                                      await axios.put(updatePaymentIntentUrl, {
                                          payment_method_id : result.paymentMethod.id,
                                      }).then(async ({ data }) => {
                                          if (data.status === 'succeeded') {
                                              that.redirectComplete();
                                          } else if (data.status === "requires_source_action" || data.status === "requires_action") {
                                              await this.cards.stripe.handleCardAction(data.payment_intent.client_secret).then(async result => {
                                                  if (result.error) {
                                                      that.errorMessage = result.error.message
                                                      that.in_progress = false
                                                  } else {
                                                      await axios.put(updatePaymentIntentUrl).then(() => {
                                                          that.redirectComplete()
                                                      }).catch((response) => {
                                                          if (response.response.status === 400) {
                                                              that.errorMessage = response.response.data.error_message;
                                                              that.in_progress = false;
                                                          } else {
                                                              console.error(response);
                                                          }
                                                      });
                                                  }
                                              });
                                          } else {
                                              console.log("Response Not Handled.", result);
                                          }
                                      }).catch((response) => {
                                          if (response.response.status === 400) {
                                              that.errorMessage = response.response.data.error_message;
                                              that.in_progress = false;
                                          } else {
                                              console.error(response);
                                          }
                                      });
                                  }
                              });
                          } else {
                            switch (this.charges.chosen_method) {
                              case 'wechat': {
                                QRCodeNew.toDataURL(data.wechat.qr_code_url, {
                                    width: this.qrSize + this.qrMargin,
                                    color: {
                                      dark: '#000036',
                                      light: '#fff'
                                    },
                                    errorCorrectionLevel: 'H',
                                    margin: this.qrMargin
                                }, (error, url) => {
                                  this.$refs.wechatQrCode.src = url
                                })

                                this.qrGeneration = false

                                break
                              }

                              case 'paynow_online': {
                                this.qrcode_data = data.paynow_online.qr_code_data;

                                if (!this.lazyPaynowQR) {
                                  this.generatePayNowQR()
                                }

                                break
                              }
                              case 'alipay': {
                                window.open(data.alipay.redirect_url, '_blank')
                                break
                              }
                              case 'grabpay': {
                                const response = await this.cards.stripe.confirmGrabPayPayment(
                                  data.payment_intent.client_secret,
                                  { return_url: this.getDomain('close', 'dashboard') },
                                  { handleActions: false }
                                )

                                window.open(response.paymentIntent.next_action.redirect_to_url.url, '_blank')
                                break
                              }

                              case 'grabpay_direct':
                              case 'grabpay_paylater': {
                                //window.open(data.redirect_url, '_blank')
                                this.suppressBeforeUnload = true
                                window.location.href = data.redirect_url
                                break
                              }
                              case 'shopee_pay': {
                                QRCodeNew.toDataURL(data.qr_content, {
                                    width: this.qrSize + this.qrMargin,
                                    color: {
                                      dark: '#000036',
                                      light: '#fff'
                                    },
                                    errorCorrectionLevel: 'H',
                                    margin: this.qrMargin
                                }, (error, url) => {
                                  this.$refs.shopeeQrCode.src = url
                                })

                                this.qrGeneration = false

                                this.runStatusCheckTimer()

                                break
                              }
                              case 'hoolah': {
                                window.open(data.redirect_url, '_blank')
                                break
                              }

                              case 'zip': {
                                //window.open(data.redirect_url, '_blank')
                                this.suppressBeforeUnload = true
                                window.location.href = data.redirect_url
                                break
                              }
                            }

                            this.pollChargeStatus()
                          }
                      }
                  }
                }, 0)
            }
        },

        async pollChargeStatus() {
            if (this.existing_charge === null) {
                return;
            }

            try {
                // Enable pusher logging - don't include this in production
                //Pusher.logToConsole = true;

                var pusher = new Pusher(this.getPusher('key'), {
                    cluster: this.getPusher('cluster'),
                });

                var channel = pusher.subscribe(this.charges.charge_object.id);
                let $this = this;

                channel.bind('App\\Events\\ChargeSucceeded', function (data) {
                    if (data.status === 'succeeded') {
                        $this.redirectComplete();
                    } else if (data.status === 'failed') {
                        $this.charges.charge_status = 'The payment was failed.'
                    } else if (data.status === 'canceled') {
                        $this.charges.charge_status = 'The payment was canceled by the customer.'
                    }
                });

                pusher.connection.bind('error', function (error) {
                    console.error('error', error);
                    $this.oldPollChargeStatus();
                });

                pusher.connection.bind('failed', function (error) {
                    console.error('error', error);
                    $this.oldPollChargeStatus();
                });
            } catch (e) {
                console.error(e);
                this.oldPollChargeStatus();
            }
        },
        async oldPollChargeStatus() {
            if (this.existing_charge === null) {
                return;
            }

            axios.get(this.getDomain('v1/', 'api') + 'payment-intent/' + this.existing_charge.id).then(({data}) => {
                if (data.charge.status === 'succeeded') {
                    this.redirectComplete()
                } else if (data.status === 'failed') {
                    this.charges.charge_status = 'The payment was failed.'
                } else if (data.status === 'canceled') {
                    this.charges.charge_status = 'The payment was canceled by the customer.'
                }
            });
        },

        getTerminalErrorMessage(data) {
            if (data.code === 'card_declined') {
                switch (data.decline_code) {
                    case 'invalid_pin':
                        return 'You have entered an incorrect pin. Please charge payment again.';
                    case'withdrawal_count_limit_exceeded':
                        return 'The customer has exceeded the balance or credit limit available on their card. Please charge payment again with a different card or another payment method.';
                    case'pin_try_exceeded':
                        return 'The allowable number of PIN tries has been exceeded. Please charge payment again with a new card or another payment method.';
                    case'call_issuer':
                    case'generic_decline':
                        return 'Payment declined. Please try again';
                }
            }

            return 'Payment declined. Please try again';
        },

        cancelCharge() {
            axios.post(this.base_url + 'charge/' + this.existing_charge.charge_id + '/cancel')
        },
        checkAmount() {
            this.errors.amount = ''

            if (this.checkout.charge_amount) {
                // remove ,
                const filteredAmount = String(this.checkout.charge_amount).replace(',', '')

                if (isNaN(filteredAmount)) {
                    this.errors.amount = 'Amount should be a number'
                } else {
                    const numAmount = Number(filteredAmount)

                    this.errors.amount = numAmount > 0.5
                        ? ''
                        : 'Amount should be greater then zero'
                }
            } else {
                this.errors.amount = 'Amount is required'
            }

            return this.errors.amount
        },
        checkEmail() {
            this.errors.email = ''

            if (this.checkout.email == null || this.checkout.email.length == 0) {
                this.errors.email = 'Email address is required'
            } else if (/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,24}))$/.test(this.checkout.email) === false) {
                this.errors.email = 'Email address is invalid'
            }

            return this.errors.email
        },
        isValidInput() {
            const newEmail = this.checkEmail()
            const newAmount = this.checkAmount()

            // Reset first
            if (this.shakingErrors.email && newEmail) {
                this.shakingErrors.email = false
            }

            if (this.shakingErrors.amount && newAmount) {
                this.shakingErrors.amount = false
            }

            this.$nextTick(() => {
                this.shakingErrors.email = newEmail
                this.shakingErrors.amount = newAmount
            })

            return !Boolean(this.errors.amount) && !Boolean(this.errors.email)
        },

        createStripeCards() {
            if (!this.cardElementMounted) {
                this.cards.card = this.cards.elements.create('card', {
                    hidePostalCode: true,
                    style: {
                        base: {
                            iconColor: '#4A50B5',
                            color: '#495057',
                            fontWeight: 400,
                            fontFamily: 'Inter, Arial, sans-serif',
                            fontSmoothing: 'antialiased',
                            fontSize: '16px',
                            '::placeholder': {
                                color: '#cecece',
                                fontWeight: 400,
                                fontFamily: 'Inter, Arial, sans-serif',
                                fontSize: '16px',
                            },
                        },
                        invalid: {
                            iconColor: '#dc3545',
                            color: '#dc3545',
                        },
                    }
                })

                // we need to do this in $nextTick to allow template v-if to be processed
                // and rendered, this function may be called from mounted()
                this.$nextTick(() => {
                    this.cards.card.mount('#card-element')

                    this.cards.card.on('change', ({error}) => {
                        this.errorMessage = error ? error.message : null
                    })

                    this.cardElementMounted = true
                })
            }
        },

        async createStripePaymentRequest(amount) {
            let that = this

            if (this.cards.payment_request && await this.cards.payment_request.canMakePayment()) {
                this.cards.payment_request.update({
                    total: {
                        label: 'Checkout Total',
                        amount: amount,
                    }
                })

                return;
            }

            this.cards.payment_request = this.cards.stripe.paymentRequest({
                country: this.business.country.toUpperCase(),
                currency: this.charge.currency,
                total: {
                    label: 'Checkout Total',
                    amount: amount,
                },
                requestPayerName: true,
                requestPayerEmail: true
            })

            try {
                // Check the availability of the Payment Request API first.
                this.cards.payment_request.canMakePayment().then(result => {
                    if (result) {
                        let paymentRequestButtonElement = this.cards.elements.create('paymentRequestButton', {
                            paymentRequest: this.cards.payment_request
                        })

                        paymentRequestButtonElement.mount('#payment-request-button')

                        this.psElementMounted = true

                        that.cards.payment_request.on('paymentmethod', function (ev) {
                            that.charges.chosen_method = 'card'
                            that.disabled_payment_method_buttons = true

                            axios.post(that.base_url + 'charge/' + that.charges.charge_object.id + '/create-payment-intent', {
                                method: that.charges.chosen_method,
                                email: ev.payerEmail
                            }).then(async ({data}) => {
                                if (data.alreadyPaid) {
                                    this.alreadyPaid()
                                } else {
                                    that.runTimeoutTimer()

                                    that.existing_charge = data

                                    var updatePaymentIntentUrl = `${that.base_url}charge/payment-intent/${that.existing_charge.id}/confirm`;

                                    await axios.put(updatePaymentIntentUrl, {
                                        payment_method_id : ev.paymentMethod.id,
                                    }).then(async ({ data }) => {
                                        if (data.status === 'succeeded') {
                                            ev.complete('success');
                                        } else if (data.status === "requires_source_action" || data.status === "requires_action") {
                                            await this.cards.stripe.handleCardAction(data.payment_intent.client_secret).then(async result => {
                                                if (result.error) {
                                                    ev.complete('fail');

                                                    that.errorMessage = result.error.message;
                                                    that.in_progress = false;
                                                    that.disabled_payment_method_buttons = false;
                                                } else {
                                                    await axios.put(updatePaymentIntentUrl).then(() => {
                                                        ev.complete('success');
                                                    }).catch((response) => {
                                                        ev.complete('fail');

                                                        that.in_progress = false;
                                                        that.disabled_payment_method_buttons = false;

                                                        if (response.response.status === 400) {
                                                            that.errorMessage = response.response.data.error_message;
                                                        } else {
                                                            that.errorMessage = 'Unknown error';

                                                            console.error(response);
                                                        }
                                                    });
                                                }
                                            });
                                        } else {
                                            ev.complete('fail');

                                            that.errorMessage = 'Unknown error';
                                            that.in_progress = false;
                                            that.disabled_payment_method_buttons = false;

                                            console.log(data);
                                        }
                                    }).catch((response) => {
                                        ev.complete('fail');

                                        that.in_progress = false;
                                        that.disabled_payment_method_buttons = false;

                                        if (response.response.status === 400) {
                                            that.errorMessage = response.response.data.error_message;
                                        } else {
                                            that.errorMessage = 'Unknown error';

                                            console.error(response);
                                        }
                                    });
                                }
                            })
                        })

                        that.cards.payment_request.on('cancel', event => {
                            that.disabled_payment_method_buttons = false
                            that.in_progress = false
                        })

                    } else {
                        that.show_payment_request_button = false
                    }
                })
            } catch (e) {
                this.show_payment_request_button = false
            }

            this.createStripeCards()
        },

        loadIcon(icon) {
            //getDomain('icons/payment-methods/'+method+'.svg')
            let urlPrefix = 'icons/'
            if (icon == 'payment-methods/paynow_online.svg') {
                return this.getDomain(urlPrefix + 'payment-methods/paynow.png', 'securecheckout')
            } else if (icon == 'payment-methods/wechat.svg') {
                return this.getDomain(urlPrefix + 'payment-methods/wechat.png', 'securecheckout')
            }

            return this.getDomain(urlPrefix + icon, 'securecheckout')
        },

        loadImage(image) {
            return this.getDomain(image, 'securecheckout')
        },

        redirectComplete() {
            this.charges.charge_status = 'succeeded'
            this.clearTimeoutTimer()

            // Do not block other code in case something goes wrong with umami
            try {
                this.recordEvent('payment_status', 'success')
            } catch (error) {
                console.log(error)
            }

            if (this.without_payment_request) {
                return this.redirectCompleteWithoutPaymentRequest();
            } else {
                let params = {
                    reference: this.data.reference,
                    status: 'completed'
                }

                if (this.data.url_complete !== null) {
                    if (this.data.url_complete.indexOf("?") != -1) {
                        window.location.href = this.data.url_complete + '&' + $.param(params)
                    } else {
                        window.location.href = this.data.url_complete + '?' + $.param(params)
                    }
                } else {
                    window.location.href = this.default_url_completed;
                }
            }
        },

        redirectCompleteWithoutPaymentRequest() {
            let that = this
            let params = {}

            params.x_account_id = that.data.account_id
            params.x_amount = that.data.amount
            params.x_currency = that.data.currency.toUpperCase()
            params.x_gateway_reference = that.existing_charge.charge_id
            params.x_reference = that.data.reference
            params.x_result = 'completed'
            params.x_signature = that.data.response_signature
            params.x_test = that.data.test
            params.x_timestamp = that.data.timestamp
            params.url_callback = that.data.url_callback

            if (that.data.plugin_provider == 'woocommerce') {
                params.x_order_id = that.data.order_id
            }

            if (that.data.url_complete.indexOf("?") != -1) {
                window.location.href = that.data.url_complete + '&' + $.param(params)
            } else {
                window.location.href = that.data.url_complete + '?' + $.param(params)
            }
        },
        payButtonOnClick() {
            switch (this.charges.chosen_method) {
                case 'alipay':
                case 'card':
                case 'grabpay':
                case 'grabpay_direct':
                case 'grabpay_paylater':
                case 'hoolah':
                case 'zip': {
                    this.pay()
                    break
                }
                case 'paynow_online':
                case 'wechat': {
                    this.isValidInput()
                    break
                }
            }
        },
        payButtonOnDisabledClick() {
            this.isValidInput()
        },
        handleScroll(event) {
            this.scrollPos = window.scrollY
            this.measureInputs()
        },
        measureInputs() {
            this.$nextTick(() => {
                if (this.$refs.measureAmount) {
                    this.amountWidth = this.$refs.measureAmount.clientWidth + 12
                }

                if (this.$refs.measureAmountMobile) {
                    this.amountWidthMobile = this.$refs.measureAmountMobile.clientWidth + 10
                }
            })
        },
        recordEvent(type, name) {
            if (window.umami) {
                window.umami.trackEvent(name, type)
            }
        },
        onDeeplinkRedirect(url) {
            this.deepLinkRedirect = true
            window.location.href = url
        },
        runTimeoutTimer() {
            this.clearTimeoutTimer()

            if (this.timeoutValue > 0) {
                this.timeoutTimer = window.setTimeout(() => {
                    this.isTimedOut = true
                }, this.timeoutValue)
            }
        },
        runStatusCheckTimer () {
          this.orderStatusPoll = window.setTimeout(this.statusCheckTimer, 60000) // 1min
        },
        async statusCheckTimer () {
          try {
            const res = await axios.get(this.getDomain(`v1/order/status/${this.existing_charge.id}`, 'api'))

            if (res.data.success) {
              this.redirectComplete()
            } else if (!this.isTimedOut) {
              this.runStatusCheckTimer()
            }
          } catch (error) {

          }
        },
        clearTimeoutTimer () {
            if (this.orderStatusPoll) {
              window.clearTimeout(this.orderStatusPoll)
              this.orderStatusPoll = null
            }

            if (this.timeoutTimer) {
              window.clearTimeout(this.timeoutTimer)
              this.timeoutTimer = null
            }
        },
        generatePayNowQR() {
          if (this.qrcode_data === 'service_unavailable') {
              alert('PayNow QR is currently not available. Please use another payment method.')
          }

          QRCodeNew.toDataURL(this.qrcode_data, {
              width: this.qrSize + this.qrMargin,
              color: {
                dark: '#840070',
                light: '#fff'
              },
              errorCorrectionLevel: 'H',
              margin: this.qrMargin
          }, (error, url) => {
            this.$refs.paynowQrCode.src = url
          })

          this.qrGeneration = false
        },
        getStripeAmount() {
            // remove thousands separator if any
            const filteredAmount = String(this.checkout.charge_amount).replace(',', '')

            // We need to round it, because sometimes JS behave weird, like 0.55*100=55.00000000000001
            return Math.round(this.zero_decimal ? filteredAmount : filteredAmount * 100)
        },
        alreadyPaid() {
            this.suppressBeforeUnload = true
            window.location = this.data.url_complete || this.default_url_completed
        },

        getCashback(payment_method) {
            return this.cashback.find(x => x.payment_provider_charge_type === payment_method) ?? null;
        },

        getCampaign(payment_method){
            return payment_method === 'paynow_online' ? this.campaign_rule : null;
        },
    }
}
</script>

<style lang="scss">
.success-container {
    margin-top: 150px;
}

.errors {
    font-size: 12px;
}

.subscribe {
    margin-top: 20px;
}

.checkout-success {
    padding-top: 100px;
}

.v-money {
    text-align: center;
    width: 80%;
}

$emailTextColor: #545454;
$breakpoint: 915px;
$applePayheight: 138px;
$fontFamily: -apple-system, BlinkMacSystemFont, sans-serif;
$emailContainerWidth: 300px;
$emailContainerPadding: 12px;

.mobile {
    @media screen and (max-width: $breakpoint) {
        display: block;
    }

    @media screen and (min-width: $breakpoint) {
        display: none;
    }
}

.mobile-flex {
    @media screen and (max-width: $breakpoint) {
        display: flex;
    }

    @media screen and (min-width: $breakpoint) {
        display: none;
    }
}

.desktop {
    @media screen and (max-width: $breakpoint) {
        display: none;
    }

    @media screen and (min-width: $breakpoint) {
        display: block;
    }
}

.desktop-flex {
    @media screen and (max-width: $breakpoint) {
        display: none;
    }

    @media screen and (min-width: $breakpoint) {
        display: flex;
    }
}

.font-weight-medium {
    font-weight: 500;
}

.line-height-1 {
    line-height: 1;
}

.hidden {
    position: absolute;
    top: 0;
    left: 0;
    height: 0;
    margin: 0 !important;
    overflow-y: hidden;
}

.error {
    color: red;
}

#mobile-top-panel {
    height: 51px;
    position: fixed;
    left: 0;
    top: 0;
    right: 0;
    z-index: 1;
    padding: 4px 8px;
    align-items: center;
    padding-right: 18px;

    svg g {
        fill: inherit;
    }

    .sign {
        font-size: 12px;
        align-self: flex-start;
        margin-top: 7px;
        margin-right: 2px;
    }

    input, .input-amount-mobile {
        background: transparent;
        border: 0;
        color: inherit;
        padding: 0;
        font-size: 28px;
        max-width: 140px;

        &:focus {
            outline: none;
        }
    }

    .currency {
        margin-top: 6px;
        margin-left: 4px;
    }
}

.checkout {
    display: flex;
    height: 100%;
    min-height: 100vh;
    align-items: stretch;
    font-family: $fontFamily;

    .test-payment-notice {
        background-color: rgb(255, 252, 235);
        border: 1px solid rgb(252, 239, 173);
        padding: 8px 16px;
        font-size: 12px;

        a {
            text-decoration: underline;
        }

        i {
            color: rgb(188, 187, 181);
            font-size: 16px;
        }
    }

    .shaking {
        animation: shake-animation 4.72s ease 0s;
        transform-origin: 50% 50%;
    }

    @keyframes shake-animation {
        0% {
            transform: translate(0, 0)
        }
        1.78571% {
            transform: translate(5px, 0)
        }
        3.57143% {
            transform: translate(0, 0)
        }
        5.35714% {
            transform: translate(5px, 0)
        }
        7.14286% {
            transform: translate(0, 0)
        }
        8.92857% {
            transform: translate(5px, 0)
        }
        10.71429% {
            transform: translate(0, 0)
        }
        100% {
            transform: translate(0, 0)
        }
    }

    @media screen and (max-width: $breakpoint) {
        .checkout-main {
            flex-direction: column;
        }
    }

    .left-panel {
        display: flex;
        flex-direction: column;

        @media screen and (max-width: $breakpoint) {
            margin: 12px;
            padding: 18px;
            border-radius: 16px;
            box-shadow: rgba(0, 0, 0, .2) 0px 5px 10px 0px;

            &.hitpay {
                box-shadow: rgb(0, 27, 94) 0px 2px 10px 0px;
            }
        }

        @media screen and (min-width: $breakpoint) {
            width: 40%;
            padding: 32px;
            min-width: 300px;
            box-shadow: rgba(0, 0, 0, .2) 0px 5px 10px 0px;
        }

        .back {
            font-size: 18px;

            &.merchant-image {
                margin-top: -8px;

                a {
                    height: 44px;
                }
            }

            a {
                display: flex;
                align-items: center;
                color: inherit;

                svg g {
                    fill: inherit;
                }

                .text-container {
                    display: flex;
                    align-items: center;
                    line-height: 1;

                    .merchant-image {
                        max-width: 103px;
                        height: 44px;
                        background-color: #D8D8D8;
                        border-radius: 5px;
                        padding: 9px 12px;
                        margin-left: 12px;
                        display: inline-flex;

                        img {
                            overflow: hidden;
                        }
                    }
                }
            }

            @media screen and (max-width: $breakpoint) {
                display: none;
            }
        }

        .left-main {
            flex-grow: 1;
            display: flex;

            input {
                background: transparent;
                border: 0;
                color: inherit;
                padding: 0;

                &:focus {
                    outline: none;
                }
            }

            @media screen and (max-width: $breakpoint) {
                flex-direction: column;
            }

            @media screen and (min-width: $breakpoint) {
                flex-direction: column;
                justify-content: center;
                align-items: center;
            }

            > * {
                margin-bottom: 4px;
            }

            .title-amount-name {
                display: flex;

                @media screen and (max-width: $breakpoint) {

                }

                @media screen and (min-width: $breakpoint) {
                    flex-direction: column;
                }

                .title-amount {
                    @media screen and (min-width: $breakpoint) {
                        text-align: center;
                    }

                    .hint {
                        @media screen and (max-width: $breakpoint) {
                            font-size: 12px;
                            font-weight: 300; // Light
                        }
                        @media screen and (min-width: $breakpoint) {
                            font-size: 22px;
                        }
                    }

                    .amount-currency {
                        display: flex;
                        align-items: center;

                        @media screen and (max-width: $breakpoint) {
                            flex-direction: row;
                        }

                        @media screen and (min-width: $breakpoint) {
                            flex-direction: column;
                        }

                        .amount {
                            display: flex;

                            .sign {
                                @media screen and (max-width: $breakpoint) {
                                    font-size: 12px;
                                    margin-top: 11px;
                                }

                                @media screen and (min-width: $breakpoint) {
                                    font-size: 20px;
                                    margin-top: 20px;
                                }

                                margin-right: 4px;
                            }

                            .input-amount {
                                @media screen and (max-width: $breakpoint) {
                                    max-width: 150px;
                                    min-width: 32px;
                                    font-size: 32px;
                                }

                                @media screen and (min-width: $breakpoint) {
                                    max-width: 220px;
                                    min-width: 45px;
                                    font-size: 45px;
                                    margin: 6px 0;
                                }
                            }
                        }

                        .currency {
                            @media screen and (max-width: $breakpoint) {
                                margin-left: 8px;
                                font-size: 13px;
                            }

                            @media screen and (min-width: $breakpoint) {
                                font-size: 18px;
                            }
                        }
                    }
                }

                .name {
                    @media screen and (max-width: $breakpoint) {
                        font-size: 18px;
                        flex-grow: 1;
                        text-align: right;
                    }

                    @media screen and (min-width: $breakpoint) {
                        font-size: 25px;
                        margin-top: 18px;
                        text-align: center;
                    }
                }
            }

            .description {
                font-size: 15px;

                @media screen and (max-width: $breakpoint) {
                    margin: 0;
                    font-size: 12px;
                    max-width: 200px;
                }

                @media screen and (min-width: $breakpoint) {
                    margin-top: 27px;
                    font-size: 20px;
                    max-width: 95%;
                }

                &.hitpay::placeholder {
                    color: rgb(127, 140, 176);
                }

                &.light::placeholder {
                    color: rgb(74, 74, 74);
                }

                &.custom-light::placeholder {
                    color: white;
                }

                &::placeholder {
                    opacity: 1;
                }
            }
        }

        .v-divider {
            height: 23px;
            width: 1px;
            background-color: white;
        }

        .copyright {
            color: inherit;
            height: 23px;
            text-align: center;
            font-size: 12px;
            margin: 44px 0;

            img {
                height: 16px;
            }

            .hitpay {
                font-weight: 700;
                font-size: 12px;
            }
        }
    }

    .main-body {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        align-items: flex-start;

        @media screen and (max-width: $breakpoint) {
            padding: 12px;
        }

        @media screen and (min-width: $breakpoint) {
            padding: 32px;
        }

        .apple-pay {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            height: $applePayheight;

            .inner {
                position: absolute;
                left: 0;
                right: 0;
                bottom: 0;
                display: flex;
                flex-direction: column;
                align-items: center;

                #payment-request-button {
                    @media screen and (max-width: $breakpoint) {
                        width: 100%;
                    }

                    @media screen and (min-width: $breakpoint) {
                        width: 300px;
                    }
                }
            }
        }

        .email-methods {
            @media screen and (max-width: $breakpoint) {
                padding: 0 6px;
            }

            @media screen and (min-width: $breakpoint) {
                width: 650px;
            }

            align-self: center;
        }

        .email {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-content: flex-start;
            padding-left: $emailContainerPadding;
            margin: 0 0 16px 0;

            span {
                color: $emailTextColor;
                font-size: 16px;
            }

            .input-container {
              border-bottom: solid 1px #979797;
              margin-top: 4px;
              padding-bottom: 4px;
              width: $emailContainerWidth;

              input {
                border: 0;
                color: black;
                font-size: 16px;

                @media (prefers-color-scheme: dark) {
                  background-color: white;
                }

                &:focus {
                    border: 0;
                    outline: none;
                }
              }
            }

            .input-error {
                span {
                    color: red;
                    margin-top: 6px;
                    font-size: 15px;
                }
            }
        }

        .payment-methods {
            display: flex;
            align-items: flex-start;
            flex-direction: column;

            span {
                color: $emailTextColor;
                margin-left: 12px;
                font-size: 16px;
            }

            .container {
                display: flex;
                padding: 0;

                &.single-method {
                    > * {
                        margin-left: $emailContainerPadding;
                        width: 100%;
                    }
                }

                &.two-three-methods {
                  > * {
                    flex-grow: 1;
                  }
                }

                @media screen and (max-width: $breakpoint) {
                  margin: 16px 0;
                  flex-wrap: wrap;
                  justify-content: center;

                      > * {
                          flex: 1 0 39%;
                          margin-bottom: 10px;
                      }
                }

                @media screen and (min-width: $breakpoint) {
                    margin: 16px 0 32px 0;
                }
            }

            .helper-text {
                align-self: center;
                font-size: 14px;
                color: #9B9B9B;
                height: 80px;

                ul {
                    list-style-type: none;
                    padding: 0;
                }
            }

            .options {
                display: flex;
                flex-direction: column;
                align-items: center;
                width: 100%;

                &.options-card {
                  max-width: 300px;
                }

                .bank-image {
                  margin: 0 2px 0 2px;
                }

                .other-banks {
                    font-size: 12px;
                    text-decoration: underline;
                }

                .card-logos {
                    margin-bottom: 32px;

                    div {
                        box-sizing: content-box;
                        width: 36px;
                        height: 22px;
                        margin: 0 8px;
                        border: solid 1px rgba(0, 0, 0, .07);
                        border-radius: 3px;
                        display: flex;
                        overflow: hidden;

                        img {
                            height: 22px;
                            width: 36px;
                        }
                    }
                }

                .qr-spinner {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    height: 231px;
                    width: 231px;
                }

                .qr-code {
                    height: 227px;
                    width: 227px;

                    &.samsung {
                      border-radius: 4px;
                      height: 231px;
                      width: 231px;
                    }

                    overflow: hidden;
                    box-shadow: rgba(0, 0, 0, .2) 0 5px 10px 0;
                }

                span {
                    margin-left: 0;
                    text-align: center;
                }

                .text-1 {
                    font-size: 13px;
                    color: rgb(94, 94, 94);
                }

                .text-2 {
                    font-size: 12px;
                    font-weight: medium;
                    color: rgb(105, 105, 105);
                }

                .refresh-button {
                    width: 100px;
                    height: 24px;
                    background-color: #D8D8D8;
                    color: rgb(86, 86, 86);
                    font-size: 14px;
                    border-radius: 8px;
                    border: none;
                    align-self: center;
                    cursor: pointer;

                    &:hover {
                        background-color: darken(#D8D8D8, 10);
                    }
                }

                #card-element {
                    @media screen and (max-width: $breakpoint) {
                        margin-bottom: 16px;
                    }

                    @media screen and (min-width: $breakpoint) {
                        margin-top: 48px;
                        margin-bottom: 32px;
                    }
                }
            }

            .text-2-mobile {
                font-size: 12px;
                color: #9b9b9b;
                text-align: center;
                margin: 16px 0 0 0;
            }
        }

        .checkout-button {
            align-self: center;
        }
    }
}

.slide-y-enter-active,
.slide-y-leave-active {
    transition: max-height .3s ease-in;
}

.slide-y-enter-to, .slide-y-leave {
    max-height: $applePayheight;
    overflow: hidden;
}

.slide-y-enter, .slide-y-leave-to {
    overflow: hidden;
    max-height: 0;
}

.fade-leave-active,
.fade-enter-active {
    transition: opacity .5s ease;
}

.fade-enter,
.fade-leave-to {
    opacity: 0;
}

.fade-slow-leave-active,
.fade-slow-enter-active {
    transition: opacity .3s linear;
}

.fade-slow-enter,
.fade-slow-leave-to {
    opacity: 0;
}
</style>
