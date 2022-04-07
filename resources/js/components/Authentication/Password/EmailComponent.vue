<template>
  <LoginRegisterLayout title="Reset password">
    <template v-if="message">
        <p>{{ message }}</p>
    </template>
    <template v-else>
      <LoginInput
        id="email"
        v-model="email"
        label="Email Address"
        :marginBottom="30"
        :error="error"
        :disabled="is_processing"/>

      <CheckoutButton
        title="Send Reset Link"
        :spinner="is_processing"
        :disabled="is_processing"
        @click="request"/>

      <div
        v-if="httpError"
        class="d-block text-center invalid-feedback my-2">
        {{ httpError }}
      </div>

      <p class="small text-center mt-5">
        <a :href="this.getDomain('login', 'dashboard')">Login instead</a>
      </p>
    </template>
  </LoginRegisterLayout>
</template>

<script>
import LoginRegisterLayout from '../../Authentication/LoginRegisterLayout'
import CheckoutButton from '../../Shop/CheckoutButton'
import LoginInput from '../../Authentication/LoginInput'

export default {
  components: {
    LoginRegisterLayout,
    CheckoutButton,
    LoginInput
  },

  data() {
      return {
          email: null,
          error: null,
          httpError: '',
          is_processing: false,
          message: null,
      };
  },

  methods: {
      async request() {
          this.error = null;
          this.httpError = ''
          this.is_processing = true;

          try {
            const res = await axios.post(this.getDomain('password/email', 'dashboard'), {
              email: this.email
            })

            this.message = res.data.message;
          } catch (error) {
              if (error.response.status === 422) {
                  _.forEach(error.response.data.errors, (value, key) => {
                      this.error = _.first(value);
                  });

                  this.displayError();
              } else {
                this.httpError = error
                this.is_processing = false;
              }

          }
      },

      displayError() {
          this.is_processing = false;

          this.scrollTo('#firstInput', 48, 0);
      },
  }
}
</script>
