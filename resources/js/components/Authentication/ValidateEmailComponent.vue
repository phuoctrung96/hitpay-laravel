<template>
  <LoginRegisterLayout title="Confirm your email address">
    <p>We have sent an email with a confirmation link to your email address. In order to complete the sign-up process, please click the confirmation link.</p>

    <p>If you do not receive a confirmation email, please check your spam folder. Also, please verify that you entered a valid email address in our sign-up form.</p>

    <p><a href="#" v-if="resendVisible" v-on:click="resendEmail()">Re-send email</a></p>

    <p>If you need assistance, please <a href="mailto:support@hit-pay.com">contact us</a></p>
  </LoginRegisterLayout>
</template>

<script>
import LoginRegisterLayout from './LoginRegisterLayout'

export default {
  name: "AuthenticationValidateEmail",
  components: {
    LoginRegisterLayout,
  },
  data() {
    return {
      'resendVisible': false
    };
  },
  mounted() {
    this.showOrHideResendBtn();
  },
  methods: {
    resendEmail() {
      this.resendVisible = false;
      axios.get(this.getDomain('email/resend', 'dashboard'), {
        headers: {
          'Accept': 'application/json',
        }
      }).then((data) => {
        alert('Please allow a few minutes for email to arrive.')

        this.showOrHideResendBtn();
      }).catch(({response: { data: { errors } } }) => {
        console.error(errors)

        this.showOrHideResendBtn();
      });
    },
    showOrHideResendBtn () {
      setTimeout(() => {
        this.resendVisible = true;
      }, 5000);
    }
  }
}
</script>
