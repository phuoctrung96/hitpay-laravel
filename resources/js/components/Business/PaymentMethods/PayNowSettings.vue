<template>
  <div class="paynow p-4">
    <h2 class="text-primary mb-3">{{ notSet ? 'Set up PayNow acceptance for your business' : 'Connected to PayNow' }}</h2>

    <div
      v-if="message"
      class="alert border-top border-left-0 border-right-0 border-bottom-0 rounded-0 mb-0"
      :class="messageSuccess ? 'alert-success' : 'alert-danger'">
      {{ message }}
    </div>

    <form @submit.prevent="save">
        <div class="form-group">
            <label for="company_uen" class="small text-secondary">Enter Company UEN or Individual NRIC</label>
            <input id="company_uen" v-model="form.company_uen" class="form-control" :class="{
                'is-invalid': errors.company_uen,
            }" autocomplete="off" autofocus :disabled="is_processing || !edit">
            <span class="invalid-feedback" role="alert">{{ errors.company_uen }}</span>
        </div>
        <div class="form-group">
            <label for="company_name" class="small text-secondary">Registered ACRA Company Name or Full Name as per NRIC</label>
            <input id="company_name" v-model="form.company_name" class="form-control" :class="{
                'is-invalid': errors.company_name,
            }" autocomplete="off" :disabled="is_processing || !edit">
            <span class="invalid-feedback" role="alert">{{ errors.company_name }}</span>
        </div>
        <div class="form-group">
            <label for="password" class="small text-secondary">Enter Your HitPay Account Password</label>
            <input id="password" type="password" v-model="form.password" class="form-control" :class="{
                'is-invalid': errors.password,
            }" autocomplete="off" :disabled="is_processing || !edit">
            <span class="invalid-feedback" role="alert">{{ errors.password }}</span>
        </div>
        <div v-if="provider" class="form-group">
            <div class="alert alert-info small"><i class="fa fa-info-circle"></i> Set up your bank account <a class="alert-link" :href="this.getDomain('business/' + this.business.id + '/settings/bank-accounts', 'dashboard')">here</a> for payout.</div>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary" :disabled="is_processing">
                {{ buttonTitle }} <i v-if="is_processing" class="fas fa-circle-notch fa-spin"></i>
            </button>
        </div>
        <p class="small text-muted mb-0">By providing this information, you agree to HitPay <a href="https://www.hitpayapp.com/privacy-and-terms">Terms and Privacy Policy</a></p>
    </form>
  </div>
</template>

<script>
export default {
  name: 'PayNowSettings',
  props: {
    provider: Object,
    // Success message to show
    success_message: {
      type: String,
      default: ''
    }
  },
  data () {
    return {
      business: window.Business,
      errors: {
        //
      },
      message: '',
      messageSuccess: false,
      form: {
        company_uen: '',
        company_name: '',
        password: '',
      },
      is_processing: false,
      edit: false,
      notSet: false
    }
  },
  mounted() {
    if (this.provider) {
      if (this.success_message) {
        this.messageSuccess = true;
        this.message = this.success_message;

        this.postHogCaptureData('paynow_setup',
          this.business.id,
          this.business.email,
          {
            email: this.business.email,
            name: this.business.name
          }
        );
      }

      this.notSet = false;
      this.form = this.provider;
    } else {
      this.edit = true;
      this.notSet = true;
    }
  },
  computed: {
    buttonTitle () {
      return this.edit
        ? Boolean(this.provider)
          ? 'Save'
          : 'Complete Setup'
        : 'Edit';
    }
  },
  methods: {
    async save() {
      this.message = '';

      if (!this.edit) {
        this.edit = true;
      } else {
        this.is_processing = true;

        try {
          this.errors = {};

          const response = await axios.post(this.getDomain('business/' + this.business.id + '/payment-provider/paynow', 'dashboard'), this.form);

          if (this.provider) {
            this.edit = false;
            this.notSet = false;
            this.form.password = '';
            this.message = response.data.success_message;
            this.messageSuccess = true;
          } else {
            window.location = this.getDomain(`business/${this.business.id}/verification`, 'dashboard');
          }
        } catch (error) {
          if (error.response.status === 422) {
            _.forEach(error.response.data.errors, (value, key) => {
                this.errors[key] = _.first(value);
            });

            this.showError(_.first(Object.keys(this.errors)));
          } else {
            this.messageSuccess = false;
            this.message = error;
          }
        }

        this.is_processing = false;
      }
    },

    showError(firstErrorKey) {
      if (firstErrorKey !== undefined) {
        this.scrollTo('#' + firstErrorKey);

        $('#' + firstErrorKey).focus();
      }

      this.is_processing = false;
    },
  }
}
</script>
