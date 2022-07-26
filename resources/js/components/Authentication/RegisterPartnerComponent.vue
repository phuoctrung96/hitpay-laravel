<template>
    <LoginRegisterLayout title="Create account">

        <LoginInput
            v-model="form.display_name"
            label="Partner Name"
            :marginBottom="30"
            autocomplete="name"
            id="dispay_name"
            :error="errors.display_name"
            :disabled="is_processing"/>

        <LoginInput
            v-model="form.website"
            label="Website Address"
            :marginBottom="30"
            autocomplete="name"
            id="website"
            :error="errors.website"
            :disabled="is_processing"/>

        <div class="login-input">
            <span>What services do you currently provide?</span>
            <div class="invalid-feedback error d-block" v-if="errors.services">
                {{ errors.services }}
            </div>
            <ul class="checkbox-list">
                <li v-for="service in services">
                    <input :disabled="is_processing" type="checkbox" v-model="form.services" :value="service">
                    {{ service }}
                    <input :disabled="is_processing" type="text"
                           v-if="service === 'Other' && form.services.indexOf('Other') >= 0" class="form-control"
                           v-model="form.other_service">
                </li>
            </ul>
        </div>

        <div class="login-input">
            <span>Which platform do you specialise in?</span>
            <ul class="checkbox-list">
                <li v-for="(platform, key) in platforms">
                    <input :disabled="is_processing" :id="`platform-${key}`" type="checkbox" v-model="form.platforms" :value="platform">
                    {{ platform }}
                </li>
            </ul>
        </div>

        <LoginInput
            v-model="form.short_description"
            label="Short description of services provided ( It would be preferred if you focus on your top 2-3 services )"
            :marginBottom="30"
            autocomplete="email"
            id="short_description"
            :error="errors.short_description"
            :disabled="is_processing"/>

        <LoginInput
            v-model="form.special_offer"
            label="Special sign up offer to HitPay Merchants (If any)"
            :marginBottom="30"
            autocomplete="email"
            id="special_offer"
            :error="errors.special_offer"
            :disabled="is_processing"/>


        <div class="login-input" style="margin-bottom: 60px;">
            <span>Upload Company Logo</span>
            <input id="logo" type="file" @change="selectLogo">
            <div class="invalid-feedback error d-block" v-if="errors.logo && errors.logo != false">
                {{ errors.logo }}
            </div>
        </div>

        <LoginInput
            v-model="form.email"
            label="Email"
            :marginBottom="30"
            autocomplete="email"
            id="email"
            :error="errors.email"
            :disabled="is_processing"/>

        <LoginInput
            v-model="form.password"
            label="Password"
            id="password"
            :marginBottom="35"
            type="password"
            :error="errors.password"
            :disabled="is_processing"/>

        <LoginInput
            v-model="form.password_confirmation"
            label="Confirm Password"
            id="password_confirmation"
            :marginBottom="35"
            type="password"
            :error="errors.password_confirmation"
            :disabled="is_processing"/>

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
            <select id="country"
                    class="custom-select bg-light"
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
            <span class="invalid-feedback" role="alert">{{ errors.country }}</span>
        </div>

        <LoginSelect
            class="mt-5"
            id="referred_channel"
            v-model="form.referred_channel"
            :options="channels"
            label="Where did you hear about HitPay"
            :error="errors.referred_channel"
            :disabled="is_processing"
        />

        <div class="mt-5" v-if="form.country === 'sg'">
            <label for="agree_with_partner_terms">
                <input :disabled="is_processing" type="checkbox" v-model="agree_with_partner_terms" value="1" class=""
                       @click="agree_with_partner_terms_error = agree_with_partner_terms != 1 ? 0 : 1"
                       id="agree_with_partner_terms">
                <span :class="{'error': agree_with_partner_terms_error}">I agree to the partner program <a href="https://drive.google.com/file/d/1WxqGcmN5TvBJYyYC1eTEd22Hi-nkEHLx/view" target="_blank" style="text-decoration: underline">terms and conditions</a></span>
            </label>
        </div>

        <div class="mt-5" v-else-if="form.country === 'my'">
          <label for="agree_with_partner_terms">
            <input :disabled="is_processing" type="checkbox" v-model="agree_with_partner_terms" value="1" class=""
                   @click="agree_with_partner_terms_error = agree_with_partner_terms != 1 ? 0 : 1"
                   id="agree_with_partner_terms">
            <span :class="{'error': agree_with_partner_terms_error}">I agree to the partner program <a href="https://drive.google.com/file/d/1WxqGcmN5TvBJYyYC1eTEd22Hi-nkEHLx/view" target="_blank" style="text-decoration: underline">terms and conditions</a></span>
          </label>
        </div>

        <div class="mt-5" v-else>
          <label for="agree_with_partner_terms">
            <input :disabled="is_processing" type="checkbox" v-model="agree_with_partner_terms" value="1" class=""
                   @click="agree_with_partner_terms_error = agree_with_partner_terms != 1 ? 0 : 1"
                   id="agree_with_partner_terms">
            <span :class="{'error': agree_with_partner_terms_error}">I agree to the partner program <a href="https://drive.google.com/file/d/1WxqGcmN5TvBJYyYC1eTEd22Hi-nkEHLx/view" target="_blank" style="text-decoration: underline">terms and conditions</a></span>
          </label>
        </div>

        <div class="notice text-center mb-4 mt-5" v-if="form.country === 'sg'">
            By clicking "Register", you agree to our <a
            href="https://www.hitpayapp.com/termsofservice" target="_blank">Terms of Service</a>, <a
            href="https://www.hitpayapp.com/privacypolicy" target="_blank">Privacy Policy</a> and <a
            href="https://www.hitpayapp.com/acceptableusepolicy" target="_blank">Acceptable Use Policy</a>. You may
            receive email from us and can opt out at any time.
        </div>

        <div class="notice text-center mb-4 mt-5" v-else-if="form.country === 'my'">
            By clicking "Register", you agree to our <a
            href="https://hitpayapp.com/en-my/termsofservice" target="_blank">Terms of Service</a>, <a
            href="https://hitpayapp.com/en-my/privacypolicy" target="_blank">Privacy Policy</a>, <a
            href="https://hitpayapp.com/en-my/acceptableusepolicy" target="_blank">Acceptable Use Policy</a> and the
            <a href="https://stripe.com/connect-account/legal">Stripe Connected Account Agreement.</a>
            You may receive email from us and can opt out at any time.
        </div>

        <div class="notice text-center mb-4 mt-5" v-else>
          By clicking "Register", you agree to our <a
          href="https://www.hitpayapp.com/termsofservice" target="_blank">Terms of Service</a>, <a
          href="https://www.hitpayapp.com/privacypolicy" target="_blank">Privacy Policy</a> and <a
          href="https://www.hitpayapp.com/acceptableusepolicy" target="_blank">Acceptable Use Policy</a>. You may
          receive email from us and can opt out at any time.
        </div>

        <CheckoutButton
            class="login-button-override"
            title="Register"
            :spinner="is_processing"
            :disabled="is_processing"
            @click="register"/>

        <div
            v-if="httpError"
            class="d-block text-center invalid-feedback my-2">
            {{ httpError }}
        </div>

        <div class="bottom-link text-center mt-4">Have an account? <a href="/login">Sign In</a></div>
    </LoginRegisterLayout>
</template>

<script>
import LoginRegisterLayout from './LoginRegisterLayout'
import CheckoutButton from '../Shop/CheckoutButton'
import LoginInput from './LoginInput'
import LoginSelect from "./LoginSelect";
import WebsiteHelper from "../../mixins/WebsiteHelper";
import Vue from "vue";
import {VueReCaptcha} from "vue-recaptcha-v3";

export default {
    components: {
        LoginSelect,
        LoginRegisterLayout,
        CheckoutButton,
        LoginInput
    },
    props: [
        'name',
        'email',
        'categories',
        'countries',
        'recaptcha_sitekey',
    ],
    mixins: [
        WebsiteHelper
    ],
    data() {
        return {
            channels: ['Google Search', 'Referral from another HitPay User', 'Social Media', 'Web Development Agency', 'Other'],
            errors: {
                //
            },
            httpError: '',
            agree_with_partner_terms: 0,
            agree_with_partner_terms_error: 0,
            form: {
                display_name: this.name,
                website: '',
                email: this.email,
                password: '',
                password_confirmation: '',
                platforms: [],
                services: [],
                other_service: '',
                short_description: '',
                special_offer: '',
                logo: '',
                referred_channel: '',
                other_referred_channel: '',
                merchant_category: '',
                business_type: 'individual',
                country: 'sg', // default to SG
                recaptcha_token: ''
            },
            is_processing: false,
            services: [
                'E-Commerce Website Design and Development',
                'Digital Marketing',
                'Co-Working Space',
                'Incorporation and Corporate Secretary Services',
                'Accountancy and Bookkeeping',
                'Photography',
                'Other Software Providers',
                'Other',
            ],
            platforms: [
                'Shopify',
                'WooCommerce',
                'PrestaShop',
                'Magento',
                'Xero',
                'Custom Websites',
                'NA',
            ]
        }
    },

    methods: {
        selectLogo(event) {
            this.form.logo = event.target.files[0];
            let oldErrors = this.errors;
            let keys = Object.keys(oldErrors);
            let i;
            this.errors = {};

            for(i in keys) {
                if(keys[i] != 'logo') {
                    this.errors[keys[i]] = oldErrors[keys[i]];
                }
            }
        },
        validateForm() {
            this.errors = {};

            if (!this.form.display_name) {
                this.errors.display_name = 'The display name field is required';
            } else if (this.form.display_name.length > 255) {
                this.errors.display_name = 'The display name may not be greater than 255 characters.';
            }

            if (!this.form.website) {
                this.errors.website = 'The website address field is required';
            } else if (!this.validURL(this.form.website)) {
                this.errors.website = 'The website address field must be valid url address.';
            }

            if (!this.form.short_description) {
                this.errors.short_description = 'The short description field is required';
            }

            if (!this.form.logo) {
                this.errors.logo = 'The logo field is required';
            }

            if (!this.form.services.length) {
                this.errors.services = 'The services field is required';
            }

            if (!this.form.country) {
                this.errors.country = 'The country field is required';
            }

            if (!this.form.email) {
                this.errors.email = 'The email field is required';
            } else if (!this.validateEmail(this.form.email)) {
                this.errors.email = 'The email field must be a valid email address.';
            } else if (this.form.email.length > 255) {
                this.errors.email = 'The email may not be greater than 255 characters.';
            }

            if (!this.form.password) {
                this.errors.password = 'The password field is required';
            } else if (!this.form.password.match(/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9])(?!.*\s).{8,}$/)) {
                this.errors.password = 'The password must contain a minimum of 8 chracters, containing 1 upper case, 1 lower case, 1 number and 1 special character';
            }

            if (!this.form.password_confirmation) {
                this.errors.password_confirmation = 'The password confirmation field is required';
            } else if (this.form.password !== this.form.password_confirmation) {
                this.errors.password = 'The password and confirmation doesn\'t match';
            }
        },
        async register() {
            this.httpError = '';
            this.is_processing = true

            this.validateForm();

            if(!this.agree_with_partner_terms) {
                this.agree_with_partner_terms_error = 1;
                this.is_processing = false;
                return false;
            }

            if (Object.keys(this.errors).length > 0) {
                this.showError(_.first(Object.keys(this.errors)));
            } else {
                try {
                    // verify CAPTCHA
                    await this.$recaptchaLoaded()
                    this.form.recaptcha_token = await this.$recaptcha('register_partner')

                    const formData = new FormData()
                    formData.append('logo', this.form.logo);
                    formData.append('display_name', this.form.display_name);
                    formData.append('website', this.form.website);
                    formData.append('email', this.form.email);
                    formData.append('password', this.form.password);
                    formData.append('password_confirmation', this.form.password_confirmation);
                    formData.append('platforms', this.form.platforms);
                    formData.append('services', this.form.services);
                    formData.append('other_service', this.form.other_service);
                    formData.append('short_description', this.form.short_description);
                    formData.append('special_offer', this.form.special_offer);
                    formData.append('referred_channel', this.form.referred_channel);
                    formData.append('other_referred_channel', this.form.other_referred_channel);
                    formData.append('merchant_category', this.form.merchant_category);
                    formData.append('business_type', this.form.business_type);
                    formData.append('country', this.form.country);
                    formData.append('name', this.form.display_name);
                    formData.append('recaptcha_token', this.form.recaptcha_token);

                    const res = await axios.post(this.getDomain('register-partner', 'dashboard'), formData)
                    window.location.href = res.data.redirect_url
                } catch (error) {
                    console.log(error);
                    if (error.response.status === 422) {
                        _.forEach(error.response.data.errors, (value, key) => {
                            this.errors[key] = _.first(value);
                        });

                        this.showError(_.first(Object.keys(this.errors)));
                    } else {
                        this.httpError = error
                        this.is_processing = false;
                    }
                }
            }
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

        validURL(str) {
            var pattern = new RegExp('^(https?:\\/\\/)?' + // protocol
                '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|' + // domain name
                '((\\d{1,3}\\.){3}\\d{1,3}))' + // OR ip (v4) address
                '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*' + // port and path
                '(\\?[;&a-z\\d%_.~+=-]*)?' + // query string
                '(\\#[-a-z\\d_]*)?$', 'i'); // fragment locator
            return !!pattern.test(str);
        }
    },

    mounted() {
        var that = this;

        this.countries.forEach(function(item, _) {
            if (item.active === true) {
                that.form.country = item.id;
            }
        });

        Vue.use(VueReCaptcha, {
          siteKey: this.recaptcha_sitekey,
          loaderOptions: {
            useRecaptchaNet: true
          }
        })
    },
}
</script>

<style lang="scss" scoped>
.notice {
    font-size: 12px;
    color: #9B9B9B;
    line-height: 1.4;
}

.checkbox-list {
    margin: 0;
    padding: 0 0 60px;
}

.checkbox-list li {
    list-style-type: none;
    margin-top: 10px;
}

.login-input{
    margin: 0px 0px 15px;
}

</style>
