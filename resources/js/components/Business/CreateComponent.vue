<template>
    <LoginRegisterLayout title="Create Business">
        <BusinessModeSwitch v-model="form.business_type"/>

        <div class="form-control loggedin-message" v-if="is_message_logout">
            <p>Logged in as <span class="email">{{email}}</span> please continue to create a business or <span class="logout" @click="doLogout()">logout</span></p>
        </div>

        <template v-if="form.business_type === 'individual'">
            <LoginInput
                id="shop_name"
                v-model="form.name"
                label="Shop Display Name"
                autocomplete="name"
                :error="errors.name"
                :disabled="is_processing"/>
        </template>

        <template v-else>
<!--            <LoginInput-->
<!--                id="email"-->
<!--                v-model="form.email"-->
<!--                label="Company Email"-->
<!--                :error="errors.email"-->
<!--                :disabled="is_processing"/>-->

            <LoginInput
                id="name"
                v-model="form.name"
                :label="(country == 'sg') ? form_names.sg.registered_company : form_names.my.registered_company"
                :error="errors.name"
                :disabled="is_processing"/>
        </template>

        <LoginSelect
            id="merchant_category"
            v-model="form.merchant_category"
            label="Category"
            :options="categories"
            :optionValue="'id'"
            :optionDisplay="'category'"
            :error="errors.merchant_category"
            :disabled="is_processing"
            :search="true"
        />

        <div class="mb-3">
            <label for="country">Country</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <div class="form-control flat-icon" id="flat-icon">
                        <div class="icon">
                            <img src="" v-bind:src="'../images/vendor/flag-icon-css/flags/4x3/' + form.country + '.svg'" alt="">
                        </div>
                    </div>
                </div>
                <select id="country"
                        class="form-control"
                        v-model="form.country"
                        :class="{'is-invalid' : errors.country}"
                        :disabled="is_processing">
                    <option
                        v-for="country in countries"
                        :value="country.id"
                        :selected="country.active"
                    >
                        {{ country.name }}
                    </option>
                </select>
            </div>
            <span class="invalid-feedback" role="alert">{{ errors.country }}</span>
        </div>

        <div class="notice mb-4" v-if="form.country=='' || form.country=='sg'">
            <p>
                By registering your account, you agree to our <a href="https://www.hitpayapp.com/termsofservice" target="_blank">
                Terms of Service</a>, <a href="https://www.hitpayapp.com/privacypolicy" target="_blank">Privacy Policy</a>,
                <a href="https://www.hitpayapp.com/acceptableusepolicy" target="_blank">Acceptable Use Policy</a> and the
                <a href="https://stripe.com/connect-account/legal">Stripe Connected Account Agreement.</a>
                You may receive email from us and can opt out at any time.
            </p>
        </div>

        <div class="notice mb-4" v-if="form.country=='my'">
            <p>
                By registering your account, you agree to our <a href="https://hitpayapp.com/my/termsofservice" target="_blank">
                Terms of Service</a>, <a href="https://hitpayapp.com/my/privacypolicy" target="_blank">Privacy Policy</a>,
                <a href="https://hitpayapp.com/my/acceptableusepolicy" target="_blank">Acceptable Use Policy</a> and the
                <a href="https://stripe.com/connect-account/legal">Stripe Connected Account Agreement.</a>
                You may receive email from us and can opt out at any time.
            </p>
        </div>

        <PhoneInput
            id="phone_number"
            @phoneInput="savePhone"
            label="Mobile Number (WhatsApp)"
            ref="phoneInput"
            :error="errors.phone_number"
            :disabled="is_processing"
        />

        <LoginInput
            id="website"
            v-model="form.website"
            label="Website"
            autocomplete="website"
            :error="errors.website"
            :disabled="is_processing"
        />
        <LoginSelect
            id="referred_channel"
            v-model="form.referred_channel"
            :options="channels"
            label="Where did you hear about HitPay"
            :error="errors.referred_channel"
            :disabled="is_processing"
        />
        <LoginInput
            v-if="form.referred_channel === 'Other'"
            id="other_referred_channel"
            v-model="form.other_referred_channel"
            :error="errors.other_referred_channel"
            :disabled="is_processing"
        />

        <div class="mb-4 text-center">
            <input type="checkbox"
               id="checkbox_agree"
               v-model="form.checkbox_agree"
               :class="{'is-invalid' : errors.checkbox_agree}"
               :disabled="is_processing">

            <span for="checkbox_agree" class="small text-muted form-check-label">
                I am an authorized representative of this business
            </span>

            <span v-if="errors.checkbox_agree" class="invalid-feedback d-block" role="alert">{{ errors.checkbox_agree }}</span>
        </div>

        <CheckoutButton
            class="login-button-override"
            title="Create Business"
            :spinner="is_processing"
            :disabled="is_processing"
            @click="register"/>

        <div
            v-if="httpError"
            class="d-block text-center invalid-feedback my-2">
            {{ httpError }}
        </div>
    </LoginRegisterLayout>
</template>

<script>
import LoginRegisterLayout from '../Authentication/LoginRegisterLayout'
import CheckoutButton from '../Shop/CheckoutButton'
import LoginInput from '../Authentication/LoginInput'
import PhoneInput from '../Authentication/PhoneInput'
import LoginSelect from '../Authentication/LoginSelect'
import BusinessModeSwitch from './BusinessModeSwitch'
import WebsiteHelper from "../../mixins/WebsiteHelper";
import Vue from 'vue'
import { VueReCaptcha } from 'vue-recaptcha-v3'

export default {
    name: 'BusinessCreate',
    components: {
        LoginRegisterLayout,
        CheckoutButton,
        LoginInput,
        BusinessModeSwitch,
        PhoneInput,
        LoginSelect
    },
    props: {
        email: String,
        categories: Array,
        referral: String,
        countries: Array,
        country: String,
        src_url: String,
        recaptcha_sitekey: String,
    },
    mixins: [
        WebsiteHelper
    ],
    data() {
        return {
            errors: {
                //
            },
            httpError: '',
            form: {
                business_type: 'company',
                country: '',
                name: '',
                email: '',
                phone_number: '',
                coutry_code: '',
                website: '',
                referred_channel: '',
                other_referred_channel: '',
                merchant_category: '',
                checkbox_agree: false,
                recaptcha_token: '',
            },
            phone_number: '',
            is_processing: false,
            is_message_logout: true,
            channels: ['Google Search', 'Referral from another HitPay User', 'Social Media', 'Web Development Agency', 'Other'],
            form_names: {
                'sg': {
                    'registered_company': 'Registered ACRA Company Name'
                },
                'my': {
                    'registered_company': 'Registered Company Name'
                }
            }
        };
    },

    methods: {
        async register() {
            this.httpError = ''
            this.errors = {}
            this.is_processing = true

            this.form.phone_number = this.phoneNumber;

            if (!this.form.name) {
                this.errors.name = `The ${this.businessNameStr} field is required`;
            } else if (this.form.name.length > 255) {
                this.errors.name = `The ${this.businessNameStr} may not be greater than 255 characters.`;
            }

            // if (this.form.business_type === 'company') {
            //     if (!this.form.email) {
            //         this.errors.email = 'The company email field is required';
            //     } else if (!this.validateEmail(this.form.email)) {
            //         this.errors.email = 'The company email field must be a valid email address.';
            //     } else if (this.form.email.length > 255) {
            //         this.errors.email = 'The company email may not be greater than 255 characters.';
            //     }
            // } else {
            //     this.form.email = this.email
            // }
            this.form.email = this.email

            if (!this.phone_number) {
                this.errors.phone_number = 'The WhatsApp Number field is required';
                // } else if (!this.validatePhoneNumber(this.form.phone_number)) {
                //     this.errors.phone_number = 'The WhatsApp Number field must be a valid Singapore phone number.';
            } else if (this.phone_number.length > 14) {
                this.errors.phone_number = 'The WhatsApp Number field may not be greater than 14 characters.';
            }

            if (!this.form.merchant_category) {
                this.errors.merchant_category = 'The category field is required';
            }

            if (!this.form.referred_channel) {
                this.errors.referred_channel = 'The referred channel field is required';
            }

            if (this.form.website === '') {
                this.errors.website = 'The website field is required';
            } else {
                /*if (!this.isValidHttpUrl(this.form.website)) {
                    this.errors.website = 'The website must valid url and with prefix http/https';
                }*/
            }

            if (this.form.referred_channel === 'Other' && this.form.other_referred_channel === '') {
                this.errors.other_referred_channel = 'Please specify the other referred channel';
            }

            if (!/(^[A-Za-z0-9.\-\&\$ ]+$)+/.test(this.form.name)) {
                this.errors.name = 'Chars, digits, spaces and dots are allowed in name';
            }

            if (!/^\+(?:[\d]*)$/.test(this.form.phone_number)) {
                this.errors.phone_number = 'Phone number should contain country code (ex. +65)';
            }

            if (this.form.referred_channel === 'Other' && this.form.other_referred_channel != '') {
                this.form.referred_channel = this.form.other_referred_channel;
            }

            if (!this.form.country) {
                this.errors.country = 'The country field is required';
            }

            this.form.referred_channel = this.form.referred_channel == '' ? null : this.form.referred_channel;

            if (!this.form.checkbox_agree) {
                this.errors.checkbox_agree = "Please confirm that you are the authorised person to represent this business";
            }

            if (Object.keys(this.errors).length > 0) {
                this.showError(_.first(Object.keys(this.errors)));
            } else {
                try {
                    // verify CAPTCHA
                    await this.$recaptchaLoaded()
                    this.form.recaptcha_token = await this.$recaptcha('create_business')

                    this.form.referral = this.referral;
                    const res = await axios.post(this.getDomain('business', 'dashboard'), this.form);
                    let businessId = res.data.redirect_url.split('/')[4];

                    try {
                      if (this.$gtm.enabled()) {
                        window.dataLayer?.push({
                          event: 'signup',
                          'business_id': businessId
                        });
                      }
                    } catch (err) {
                      // do nothing?
                    }

                    window.location.href = res.data.redirect_url;

                    this.postHogCaptureData('business_created',
                        businessId,
                        this.form.email,
                        {
                            email: this.form.email,
                            name: this.form.name,
                            type: this.form.business_type,
                            category: this.form.merchant_category,
                            referred_by: this.form.referred_channel
                        });
                } catch (error) {
                    if (error.response.status === 422) {
                        _.forEach(error.response.data.errors, (value, key) => {
                            this.errors[key] = _.first(value);
                        });

                        this.showError(_.first(Object.keys(this.errors)));
                    } else if(error.response.status === 403){
                        this.httpError = error.response.data.message;
                        this.is_processing = false;
                    }else {
                        this.httpError = error
                        this.is_processing = false;
                    }
                }
            }
        },
        savePhone(event, phone, countryCode) {
            this.phone_number = phone;
            this.form.coutry_code = countryCode;
        },

        showError(firstErrorKey) {
            if (firstErrorKey === 'recaptcha_token') {
                alert('Invalid CAPTCHA, please refresh the page and try again');
            }

            if (firstErrorKey !== undefined) {
                this.scrollTo('#' + firstErrorKey);

                $('#' + firstErrorKey).focus();
            }

            this.is_processing = false;
        },
        isValidHttpUrl(string) {
            let url;

            try {
                url = new URL(string);
            } catch (_) {
                return false;
            }

            return url.protocol === "http:" || url.protocol === "https:";
        },

        async doLogout () {
            this.is_processing = true
            await axios.post(`/logout`, {
                csrf: this.csrf
            })

            this.is_processing = false;

            window.location.href = this.getDomain('', 'dashboard')
        }
    },

    mounted() {
        var that = this;

        _.forEach(this.countries, function(item, _) {
            if (item.active === true) {
                that.form.country = item.id;
            }
        });

        if(this.src_url == "registration"){
            this.is_message_logout = false;
        }

        Vue.use(VueReCaptcha, {
          siteKey: this.recaptcha_sitekey,
          loaderOptions: {
            useRecaptchaNet: true
          }
        })
    },

    computed: {
        businessNameStr() {
            return this.form.business_type === 'individual' ? 'shop display name' : 'business name'
        },
        phoneNumber() {
            return this.form.coutry_code + this.phone_number;
        }
    },

    watch: {
        'form.country' : {
            handler(value) {
                if (value) {
                    this.$refs.phoneInput.applyCountry(value);
                }
            }
        }
    }
}
</script>
