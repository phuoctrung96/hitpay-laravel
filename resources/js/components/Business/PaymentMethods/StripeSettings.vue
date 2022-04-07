<template>
  <div class="p-4">
      <template v-if="provider">
          <h2 class="text-primary mb-4">Connected To Stripe</h2>

          <div>
              <label class="small text-uppercase">Business Name</label>
              <p class="h4 mb-3">{{ businessName }}</p>
              <label class="small text-uppercase">Email Address</label>
              <p class="h4 mb-0">{{ businessEmail }}</p>
          </div>

          <div v-if="canRemoveStripeAccount()"
            class="mt-4">
              <p class="font-weight-bold text-danger">Remove Stripe Account</p>

              <div class="form-group">
                  <label for="validationCustomUsername" class="small">Password</label>

                  <input
                    v-model="form.password"
                    type="password"
                    name="password"
                    class="form-control bg-light"
                    :class="{ 'is-invalid': errors.password }"
                    id="password"
                    required>

                  <span class="invalid-feedback" role="alert">{{ errors.password }}</span>
              </div>
              <button
                class="btn btn-danger btn-sm"
                :disabled="is_processing"
                @click="onRemoveStripe">
                Remove Stripe Account <i v-if="is_processing" class="fas fa-circle-notch fa-spin"></i>
              </button>
          </div>
      </template>

      <template v-else>
        <h2 class="text-primary mb-0">Set up Stripe</h2>
        <p class="my-2">We use Stripe to make sure you get paid on time and to keep your personal bank and details secure</p>

        <div class="mt-3">
            <a :href="`/business/${business.id}/payment-provider/stripe/redirect`">
              <img src="/icons/stripe-connect.png" width="190" alt="Stripe Connect">
            </a>
        </div>
      </template>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'StripeSettings',
  props: {
    business: Object,
    provider: Object,
    user: Object
  },
  data () {
    return {
      //csrf token
      csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      is_processing: false,
      errors: {},
      form: {
        password: ''
      }
    }
  },
  mounted() {
    var businessEmail = this.businessEmail;
    var businessName = this.businessName;

    this.postHogCaptureData('stripe_setup',this.businessId,businessEmail,{ email: businessEmail, name: businessName});
  },
  methods: {
    canRemoveStripeAccount() {
      return this.hasPermission('canRemoveStripeAccount');
    },
      hasPermission(permission) {
          return this.user.businessUsersList.filter((businessUser) => {
              return businessUser.business_id == this.business.id && businessUser.permissions[permission];
          }).length;
      },
    async onRemoveStripe () {
      this.is_processing = true

      try {
        this.errors = {}

        await axios.post(`/business/${this.business.id}/payment-provider/stripe`, {
          _method: 'delete',
          _token: this.csrf,
          ...this.form
        }).then(({data}) => {
            location.reload();
        });

        this.form.password = ''
      } catch (error) {
        if (error.response.status === 422) {
          _.forEach(error.response.data.errors, (value, key) => {
              this.errors[key] = _.first(value);
          });

          this.showError(_.first(Object.keys(this.errors)))
        }
      }

      this.is_processing = false
    },

    showError(firstErrorKey) {
      if (firstErrorKey !== undefined) {
        this.scrollTo('#' + firstErrorKey)

        $('#' + firstErrorKey).focus()
      }
    }
  },
    computed: {
        businessName() {
            let businessName = 'Not Detected';

            if (this.provider.data.hasOwnProperty('business_name')) {
                businessName = this.provider.data['business_name'];
            }

            if (this.provider.data.hasOwnProperty('display_name')) {
                businessName = this.provider.data['display_name'];
            }

            return businessName;
        },

        businessEmail() {
            let businessEmail = 'Not Detected';

            if (this.provider.data.hasOwnProperty('email')) {
                businessEmail = this.provider.data['email'];
            }

            return businessEmail;
        },
    }
}
</script>
