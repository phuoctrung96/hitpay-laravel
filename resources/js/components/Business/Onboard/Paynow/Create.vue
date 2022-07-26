<template>
    <LoginRegisterLayout title="Bank Setup">
        <div class="mb-3">
            <div
                v-if="message"
                class="alert border-top border-left-0 border-right-0 border-bottom-0 rounded-0 mb-0"
                :class="messageSuccess ? 'alert-success' : 'alert-danger'">
                {{ message }}
            </div>
        </div>
        <div class="mb-3">
            <div class="form-group">
                <div class="switch-mode form-control mb-2" v-if="!is_staging_env">
                    <span>You can edit this information anytime</span>
                </div>
                <div class="switch-mode form-control" v-if="is_staging_env">
                    <span>You're currently in test mode</span>
                    <button @click="fillRandom()"> Fill randomly </button>
                </div>
            </div>
            <div class="form-group">
                <label for="company_uen">
                    {{ business.country === 'sg' ? form_names.sg.company_identity : form_names.my.company_identity }}
                </label>
                <input
                    id="company_uen"
                    v-model="form.company_uen"
                    class="form-control" :class="{'is-invalid': errors.company_uen}"
                    autocomplete="off"
                    autofocus
                    :disabled="is_processing">
                <span class="invalid-feedback" role="alert">
                    {{ errors.company_uen }}
                </span>
            </div>

            <div class="form-group">
                <label for="company_name">
                    {{ business.country === 'sg' ? form_names.sg.company_name : form_names.my.company_name }}
                </label>
                <input
                    id="company_name"
                    v-model="form.company_name"
                    class="form-control"
                    :class="{'is-invalid': errors.company_name,}"
                    autocomplete="off"
                    :disabled="is_processing">
                <span class="invalid-feedback" role="alert">
                    {{ errors.company_name }}
                </span>
            </div>

            <div class="form-group">
                <label for="bank_account_name">
                    Bank Account Name
                </label>
                <input maxlength="160"
                    id="bank_account_name"
                    v-model="form.bank_account_name"
                    class="form-control"
                    :class="{'is-invalid': errors.bank_account_name,}"
                    autocomplete="off"
                    :disabled="is_processing">
                <span class="invalid-feedback" role="alert">
                    {{ errors.bank_account_name }}
                </span>
            </div>

            <template v-if="select_bank_visible">
              <div class="form-group">
                  <label for="bank_swift_code">
                      Select Bank
                  </label>
                  <select
                      id="bank_swift_code"
                      class="custom-select"
                      v-model="form.bank_id"
                      :class="{'is-invalid' : errors.bank_id}"
                      :disabled="is_processing">
                      <option value="" disabled>Select Bank</option>
                      <option
                          v-for="bank in banks_list"
                          :value="bank.id">
                          {{ bank.name }}
                      </option>
                  </select>
                  <span class="invalid-feedback" role="alert">
                      {{ errors.bank_id }}
                  </span>
              </div>

              <div v-if="branches.length > 0" id="group_branch" class="form-group">
                  <label class="col-form-label">Select Branch</label>
                  <div class="input-group">
                      <select
                          v-model="form.bank_branch_code"
                          :class="getSelectClasses('branch_code')"
                          :disabled="is_processing || !form.bank_id || branches.length <= 0">
                          <option value="" disabled>Please select a branch</option>
                          <option v-for="branch in branches" :value="branch.code" :key="branch.code">[{{ branch.code }}] {{ branch.name }}</option>
                      </select>
                  </div>
                  <span class="text-danger small" role="alert" v-if="errors.bank_branch_code">{{ errors.bank_branch_code }}</span>
              </div>
            </template>
            <template v-else>
              <div class="form-group" v-if="routing_number_visible">
                <label for="bank_routing_number">
                  Routing number
                </label>
                <input
                  id="bank_routing_number"
                  v-model="form.bank_routing_number"
                  class="form-control" :class="{'is-invalid': errors.bank_routing_number}"
                  autocomplete="off"
                />
                <span class="invalid-feedback" role="alert">
                    {{ errors.bank_routing_number }}
                </span>
              </div>
              <div class="form-group" v-if="swift_visible">
                <label for="bank_swift_code">
                  SWIFT code
                </label>
                <input
                  id="bank_swift_code"
                  v-model="form.bank_swift_code"
                  class="form-control" :class="{'is-invalid': errors.bank_swift_code}"
                  autocomplete="off"
                />
                <span class="invalid-feedback" role="alert">
                    {{ errors.bank_swift_code }}
                </span>
              </div>
            </template>

            <div class="form-group">
                <label for="bank_account_no">
                    Enter Bank Account No
                </label>
                <input
                    id="bank_account_no"
                    v-model="form.bank_account_no"
                    class="form-control" :class="{'is-invalid': errors.bank_account_no}"
                    autocomplete="off"
                />
                <span class="invalid-feedback" role="alert">
                    {{ errors.bank_account_no }}
                </span>
            </div>

            <CheckoutButton
                class="login-button-override mt-5 mb-5"
                title="Save Bank Setup"
                :spinner="is_processing"
                :disabled="is_processing"
                @click="save"/>
        </div>
    </LoginRegisterLayout>
</template>

<script>
import LoginRegisterLayout from "../../../Authentication/LoginRegisterLayout";
import CheckoutButton from '../../../Shop/CheckoutButton';

export default {
    name : "Components.Business.Onboard.Paynow.Create",
    components: {
        LoginRegisterLayout,
        CheckoutButton,
    },
    props: {
        provider: Object,
        banks_list: Array,
        success_message: {
            type: String,
            default: ''
        },
        bank_fields : Object
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
                bank_account_name: '',
                bank_id: '',
                bank_branch_code: '',
                bank_account_no: '',
                bank_account_no_confirmation: '',
                password: '',
                bank_routing_number: '',
                bank_swift_code: ''
            },
            is_processing: false,
            branches : [],
            form_names: {
                'sg': {
                    'company_identity': 'Enter Company UEN or Individual NRIC',
                    'company_name': 'Registered ACRA Company Name or Full Name as per NRIC',
                },
                'my': {
                    'company_identity': 'Company Identity',
                    'company_name': 'Company Name',
                }
            },
            is_staging_env: false,
            select_bank_visible : ['sg', 'my'].includes(window.Business.country),
            routing_number_visible : this.bank_fields.routing_number.includes(window.Business.country),
            swift_visible : this.bank_fields.swift.includes(window.Business.country)
        }
    },
    mounted() {
        let domain = this.getDomain();
        if(domain.includes('staging') || domain.includes('sandbox') || domain.includes('src.test')){
            this.is_staging_env = true;
        }
    },
    methods: {
        async save() {
            this.message = ''

            this.is_processing = true;

            try {
                this.errors = {}

                if (!this.form.company_uen) {
                    this.errors.company_uen = "Please fill this input.";
                }

                if (!this.form.company_name) {
                    this.errors.company_name = "Please fill this input.";
                }

                if (!this.form.bank_account_name) {
                    this.errors.bank_account_name = "Please input bank account name.";
                }

                if (this.select_bank_visible) {
                  if (!this.form.bank_id) {
                    this.errors.bank_id = "Please select a bank.";
                  }

                  if (this.branches.length > 0 && !this.form.bank_branch_code) {
                    this.errors.bank_branch_code = "Please select the branch of the bank.";
                  }
                } else {
                  if (this.routing_number_visible && !this.form.bank_routing_number) {
                    this.errors.bank_routing_number = "Please fill this input.";
                  }

                  if (this.swift_visible && !this.form.bank_swift_code) {
                    this.errors.bank_swift_code = "Please fill this input.";
                  }
                }

                if (!this.form.bank_account_no) {
                    this.errors.bank_account_no = "Please input bank account no";
                }

                if (Object.keys(this.errors).length > 0) {
                    this.showError(_.first(Object.keys(this.errors)));
                    return;
                }

                const response = await axios.post(this.getDomain('business/' + this.business.id + '/onboard/paynow', 'dashboard'), this.form)

                this.postHogCaptureData('onboard_bank_setup',
                    this.business.id,
                    this.business.email,
                    {
                        email: this.business.email,
                        name: this.business.name
                    }
                );

                this.is_processing = false;

                window.location = response.data.url;
            } catch (error) {
                if (error.response.status === 422) {
                    _.forEach(error.response.data.errors, (value, key) => {
                        this.errors[key] = _.first(value);
                    });

                    this.showError(_.first(Object.keys(this.errors)));
                } else {
                    this.messageSuccess = false
                    this.message = error
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

        getSelectClasses(field = null) {
            let classes = [ "custom-select" ];

            if (field !== null) {
                if (this.errors[field]) {
                    classes.push("is-invalid");
                }
            }

            if (this.is_creating) {
                classes.push("bg-light");
            }

            return classes.join(" ");
        },

        fillRandom() {
            let result           = '';
            let characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            let charactersLength = characters.length;
            for ( var i = 0; i < 8; i++ ) {
                result += characters.charAt(Math.floor(Math.random() * charactersLength));
            }

            this.form.company_uen = result;
            this.form.company_name = this.business.name;
            this.form.bank_account_name = this.business.name;
            this.form.company_name = this.business.name;

            let test_bank = this.getTestBankAccounts(this.business.country);

            if (test_bank) {
              // bank from the list
              if (test_bank['bank_id'] !== undefined) {
                this.form.bank_id = test_bank['bank_id'];

                let bank = _.first(_.filter(this.banks_list, ({ id }) => {
                  return id === this.form.bank_id;
                }));

                this.branches = bank.branches;
                if(bank.branches.length > 0){
                  this.form.bank_branch_code = bank.branches[Math.floor(Math.random()*bank.branches.length)].code;
                }
              }

              // routing number
              if (test_bank['routing_number'] !== undefined) {
                this.form.bank_routing_number = test_bank['routing_number'];
              }

              // bank account number
              if (test_bank['bank_account'] !== undefined) {
                this.form.bank_account_no = test_bank['bank_account'];
              }
            }
        },

        getTestBankAccounts(country) {
          let test_bank_accounts = {
            // routing number countries
            'au': {
              'routing_number': '110000',
              'bank_account': '000123456'
            },
            'us': {
              'routing_number': '110000000',
              'bank_account': '000123456789'
            },
            'gb': {
              'routing_number': '108800',
              'bank_account': 'GB82WEST12345698765432'
            },
            'ca': {
              'routing_number': '11000-000',
              'bank_account': '000123456789'
            },
            'br': {
              'routing_number': '110-0000',
              'bank_account': '0001234'
            },
            'hk': {
              'routing_number': '110-000',
              'bank_account': '000123-456'
            },
            'jp': {
              'routing_number': '1100000',
              'bank_account': '0001234'
            },
            'in': {
              'routing_number': 'HDFC0000261',
              'bank_account': '000123456789'
            },
            // countries with banks list
            'sg': {
              'bank_id': this.banks_list[Math.floor(Math.random()*this.banks_list.length)].id,
              'bank_account': '000123456'
            },
            'my': {
              'bank_id': this.banks_list[Math.floor(Math.random()*this.banks_list.length)].id,
              'bank_account': '000123456000'
            },
            // only bank account numer
            'nz': {
              'bank_account': '1100000000000010'
            },
            'mx': {
              'bank_account': '000000001234567897'
            },
            // iban countries
            'ae': {
              'bank_account': 'AE070331234567890123456'
            },
            'at': {
              'bank_account': 'AT611904300234573201'
            },
            'be': {
              'bank_account': 'BE62510007547061'
            },
            'bg': {
              'bank_account': 'BG80BNBG96611020345678'
            },
            'ch': {
              'bank_account': 'CH9300762011623852957'
            },
            'cy': {
              'bank_account': 'CY17002001280000001200527600'
            },
            'cz': {
              'bank_account': 'CZ6508000000192000145399'
            },
            'de': {
              'bank_account': 'DE55370400440532014000'
            },
            'dk': {
              'bank_account': 'DK5000400440116243'
            },
            'ee': {
              'bank_account': 'EE382200221020145685'
            },
            'es': {
              'bank_account': 'ES0700120345030000067890'
            },
            'fi': {
              'bank_account': 'FI2112345600000785'
            },
            'fr': {
              'bank_account': 'FR1420041010050500013M02606'
            },
            'gr': {
              'bank_account': 'GR1601101250000000012300695'
            },
            'hu': {
              'bank_account': 'HU42117730161111101800000000'
            },
            'ie': {
              'bank_account': 'IE29AIBK93115212345678'
            },
            'it': {
              'bank_account': 'IT40S0542811101000000123456'
            },
            'li': {
              'bank_account': 'LI21088100002324013AA'
            },
            'lt': {
              'bank_account': 'LT121000011101001000'
            },
            'lu': {
              'bank_account': 'LU280019400644750000'
            },
            'lv': {
              'bank_account': 'LV80BANK0000435195001'
            },
            'mt': {
              'bank_account': 'MT84MALT011000012345MTLCAST001S'
            },
            'nl': {
              'bank_account': 'NL39RABO0300065264'
            },
            'no': {
              'bank_account': 'NO9386011117947'
            },
            'pl': {
              'bank_account': 'PL61109010140000071219812874'
            },
            'pt': {
              'bank_account': 'PT50000201231234567890154'
            },
            'ro': {
              'bank_account': 'RO49AAAA1B31007593840000'
            },
            'se': {
              'bank_account': 'SE3550000000054910000003'
            },
            'si': {
              'bank_account': 'SI56263300012039086'
            },
            'sk': {
              'bank_account': 'SK3112000000198742637541'
            },
          };

          if (test_bank_accounts[country] === undefined) {
            console.log('Missing test bank account details for ' + country);
          }

          return test_bank_accounts[country];
        }
    },

    watch : {
        'form.bank_id' : {
            handler(value) {
                if (value) {
                    let bank = _.first(_.filter(this.banks_list, ({ id }) => {
                        return id === value;
                    }));

                    this.branches = bank.branches;
                } else {
                    this.branches = [];
                    this.form.bank_branch_code = '';
                }
            },
            deep : true,
        },
    },
};
</script>
