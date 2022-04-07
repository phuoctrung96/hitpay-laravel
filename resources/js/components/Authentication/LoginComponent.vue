<template>
  <LoginRegisterLayout title="Login">
    <LoginInput
      id="firstInput"
      v-model="form.email"
      label="Email address"
      autofocus
      :marginBottom="30"
      autocomplete="email"
      :error="errors.email"
      :disabled="is_processing || remaining_seconds > 0"/>

    <LoginInput
      v-model="form.password"
      label="Password"
      type="password"
      :error="errors.password"
      :disabled="is_processing || remaining_seconds > 0"/>

    <small class="form-text text-muted mb-4"><a :href="reset_password_url">Forgot password?</a></small>

    <CheckoutButton
      class="login-button-override"
      title="Login"
      :spinner="is_processing && remaining_seconds === 0"
      :disabled="is_processing || remaining_seconds > 0"
      @click="login"/>

    <div
      v-if="httpError"
      class="d-block text-center invalid-feedback my-2">
      {{ httpError }}
    </div>

    <small
      v-if="remaining_seconds > 0"
      class="form-text text-danger">
      Too many attempts, please try again in {{ remaining_seconds }} seconds.
    </small>

    <div class="bottom-link text-center mt-4">Does not have an account? <a href="/register">Register</a></div>

    <div class="text-center mt-4"><a href="https://www.hitpayapp.com/termsofservice">Terms of Service</a></div>
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
  props: {
    login_form_data: Array
  },
  data() {
    return {
      errors: {
          //
      },
      httpError: '',
      form: {
        email: '',
        password: '',
      },
      is_processing: false,
      remaining_seconds: 0,
      reset_password_url: '',
    }
  },

  beforeMount() {
      if (this.login_form_data._old_input) {
          _.each(this.login_form_data._old_input, (value, key) => {
              this.form[key] = value;
          });
      }

      if (this.login_form_data.remaining_seconds) {
          this.setRemainingSeconds(this.login_form_data.remaining_seconds);
      } else if (this.login_form_data.errors) {
          _.each(this.login_form_data.errors, (value, key) => {
              this.errors[key] = value;
          });
      }

      // todo check the focus field
  },

  mounted() {
    this.reset_password_url = this.getDomain('password/reset', 'dashboard');
  },

  methods: {
      async login() {
          this.httpError = ''
          this.errors = {}
          this.is_processing = true

          if (!this.form.email) {
              this.errors.email = 'The email address field is required.';
          }

          if (!this.form.password) {
              this.errors.password = 'The password field is required.';
          }

          if (Object.keys(this.errors).length > 0) {
              this.displayError();
          } else {
            try {
              const res = await axios.post(this.getDomain('login', 'dashboard'), this.form)
              window.location.href = res.data.redirect_url
            } catch (error) {
              if (error.response.status === 422) {
                  _.forEach(error.response.data.errors, (value, key) => {
                      this.errors[key] = _.first(value);
                  });

                  this.displayError();
              } else if (error.response.status === 429) {
                  this.is_processing = false;
                  this.setRemainingSeconds(error.response.data.remaining_seconds);
              } else {
                this.httpError = error
                this.is_processing = false;
              }
            }
          }
      },

      displayError() {
          this.is_processing = false;
          this.scrollTo('#firstInput', 48, 0);
      },

      setRemainingSeconds(value) {
          this.remaining_seconds = value;

          let counter = setInterval(() => {
              this.remaining_seconds--;

              if (this.remaining_seconds === 0) {
                  clearInterval(counter);
              }
          }, 1000);
      },
  },
}
</script>

<style lang="scss">
.login-button-override {
  width: 100% !important;
}
</style>
