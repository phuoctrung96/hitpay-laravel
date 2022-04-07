<template>
  <LoginRegisterLayout title="Create account">
    <LoginInput
      v-model="form.email"
      label="Email"
      :marginBottom="30"
      autocomplete="email"
      :error="errors.email"
      :disabled="true"/>

    <LoginInput
      v-model="form.display_name"
      label="Full Name"
      :marginBottom="30"
      autocomplete="name"
      :error="errors.display_name"
      :disabled="is_processing"/>

    <LoginInput
      v-model="form.password"
      label="Password"
      :marginBottom="35"
      type="password"
      :error="errors.password"
      :disabled="is_processing"/>

    <LoginInput
      v-model="form.password_confirmation"
      label="Confirm Password"
      :marginBottom="35"
      type="password"
      :error="errors.password_confirmation"
      :disabled="is_processing"/>

    <div class="notice text-center mb-4">By clicking "Register", you agree to our <a href="https://www.hitpayapp.com/termsofservice" target="_blank">Terms of Service</a>, <a href="https://www.hitpayapp.com/privacypolicy" target="_blank">Privacy Policy</a> and <a href="https://www.hitpayapp.com/acceptableusepolicy" target="_blank">Acceptable Use Policy</a>. You may receive email from us and can opt out at any time.</div>

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

export default {
  components: {
    LoginRegisterLayout,
    CheckoutButton,
    LoginInput
  },
  props: ['hash', 'email'],
  data() {
    return {
        errors: {
            //
        },
        httpError: '',
        form: {
            display_name: this.name,
            email: this.email,
            password: '',
            password_confirmation: '',
        },
        is_processing: false,
    }
  },

  methods: {
      async register() {
          this.httpError = ''
          this.errors = {}
          this.is_processing = true

          if (!this.form.display_name) {
              this.errors.display_name = 'The display name field is required';
          } else if (this.form.display_name.length > 255) {
              this.errors.display_name = 'The display name may not be greater than 255 characters.';
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
              const res =  await axios.post(this.getDomain('register-complete/' + this.hash, 'dashboard'), this.form)
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
          if (firstErrorKey !== undefined) {
              this.scrollTo('#' + firstErrorKey);

              $('#' + firstErrorKey).focus();
          }

          this.is_processing = false;
      },
  },
}
</script>

<style lang="scss" scoped>
.notice {
  font-size: 12px;
  color: #9B9B9B;
  line-height: 1.4;
}
</style>
