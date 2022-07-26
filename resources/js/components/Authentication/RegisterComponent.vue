<template>
  <LoginRegisterLayout title="Create Account" step="step1">
      <LoginInput
          v-model="form.email"
          label="Email"
          :marginBottom="30"
          autocomplete="email"
          :error="errors.email"
          :disabled="is_processing"/>

      <LoginInput
          v-model="form.first_name"
          label="First Name"
          :marginBottom="30"
          autocomplete="first_name"
          :error="errors.first_name"
          :disabled="is_processing"/>

      <LoginInput
          v-model="form.last_name"
          label="Last Name"
          :marginBottom="30"
          autocomplete="last_name"
          :error="errors.last_name"
          :disabled="is_processing"/>
      <div class="is-password">
        <LoginInput
          v-if="showPassword"
          v-model="form.password"
          label="Password"
          :marginBottom="35"
          type="password"
          :error="errors.password"
          :disabled="is_processing"
          :isPassword="true"/>

        <LoginInput
          v-else
          v-model="form.password"
          label="Password"
          :marginBottom="35"
          type="text"
          :error="errors.password"
          :disabled="is_processing"/>
        <span class="sh-password show" v-if="showPassword" @click="togglePassword"><img src="/images/ico-show-password.svg"></span>
        <span class="sh-password hide" v-else @click="togglePassword"><img src="/images/ico-hide-password.svg"></span>
      </div>
      <div class="is-password">
        <LoginInput
          v-if="showConfirmPassword"
          v-model="form.password_confirmation"
          label="Confirm Password"
          :marginBottom="35"
          type="password"
          :error="errors.password_confirmation"
          :disabled="is_processing"/>

        <LoginInput
          v-else
          v-model="form.password_confirmation"
          label="Confirm Password"
          :marginBottom="35"
          type="text"
          :error="errors.password_confirmation"
          :disabled="is_processing"/>
        <span class="sh-password show" v-if="showConfirmPassword" @click="toggleConfirmPassword"><img src="/images/ico-show-password.svg"></span>
        <span class="sh-password hide" v-else @click="toggleConfirmPassword"><img src="/images/ico-hide-password.svg"></span>
      </div>
      <!--CAPTCHA-->

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
import LoginSelect from './LoginSelect'
import Vue from 'vue'
import { VueReCaptcha } from 'vue-recaptcha-v3'

export default {
  name: "AuthenticationRegister",
  components: {
    LoginRegisterLayout,
    CheckoutButton,
    LoginInput,
    LoginSelect,
  },
  props: {
      name: String,
      email: String,
      referral: String,
      business_referral: String,
      countries: Array,
      recaptcha_sitekey: String,
  },
  data() {
    return {
        errors: {
            //
        },
        httpError: '',
        form: {
            first_name: this.name,
            last_name: '',
            email: this.email,
            password: '',
            password_confirmation: '',
            recaptcha_token: ''
        },
        is_processing: false,
        showPassword: true,
        showConfirmPassword: true,
    }
  },
  mounted() {
    Vue.use(VueReCaptcha, {
      siteKey: this.recaptcha_sitekey,
      loaderOptions: {
        useRecaptchaNet: true
      }
    })
  },
    methods: {
      async register() {
          this.httpError = ''
          this.errors = {}
          this.is_processing = true

          if (!this.form.first_name) {
              this.errors.first_name = 'The first name field is required';
          } else if (this.form.first_name.length > 255) {
              this.errors.first_name = 'The first name may not be greater than 255 characters.';
          }

          if (!this.form.last_name) {
              this.errors.last_name = 'The last name field is required';
          } else if (this.form.last_name.length > 255) {
              this.errors.last_name = 'The last name may not be greater than 255 characters.';
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

          if (Object.keys(this.errors).length > 0) {
              this.showError(_.first(Object.keys(this.errors)));
          } else {
            try {
              // verify CAPTCHA
              await this.$recaptchaLoaded()
              this.form.recaptcha_token = await this.$recaptcha('register')

              this.form.partner_referral = this.referral;
              this.form.business_referral = this.business_referral;

              const res =  await axios.post(this.getDomain('register', 'dashboard'), this.form)

              window.location.href = res.data.redirect_url
            } catch (error) {
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
      togglePassword(){
           this.showPassword = !this.showPassword;
      },
      toggleConfirmPassword(){
           this.showConfirmPassword = !this.showConfirmPassword;
      }
  },
}
</script>

<style lang="scss" scoped>
  .account-verification{
    .notice {
      font-size: 12px;
      color: #545454;
      line-height: 1.5;
    }
  }
</style>
