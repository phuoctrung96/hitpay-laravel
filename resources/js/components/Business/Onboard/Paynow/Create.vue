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
                <label for="company_uen">
                    {{ business.country == 'sg' ? form_names.sg.company_identity : form_names.my.company_identity }}
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
                    {{ business.country == 'sg' ? form_names.sg.company_name : form_names.my.company_name }}
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
                <input
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

            <div class="form-group">
                <label for="bank_swift_code">
                    Select Bank
                </label>
                <select
                    id="bank_swift_code"
                    class="custom-select bg-light"
                    v-model="form.bank_swift_code"
                    :class="{'is-invalid' : errors.bank_swift_code}"
                    :disabled="is_processing">
                    <option value="" disabled>Select Bank</option>
                    <option
                        v-for="bank in banks_list"
                        :value="bank.swift_code">
                        {{ bank.name }}
                    </option>
                </select>
                <span class="invalid-feedback" role="alert">
                    {{ errors.bank_swift_code }}
                </span>
            </div>

            <div v-if="branches.length > 0" id="group_branch" class="form-group">
                <label class="col-form-label">Select Branch</label>
                <div class="input-group">
                    <select
                        v-model="form.bank_branch_code"
                        :class="getSelectClasses('branch_code')"
                        :disabled="is_processing || !form.bank_swift_code || branches.length <= 0">
                        <option value="" disabled>Please select a branch</option>
                        <option v-for="branch in branches" :value="branch.code">[{{ branch.code }}] {{ branch.name }}</option>
                    </select>
                </div>
                <span class="text-danger small" role="alert" v-if="errors.bank_branch_code">{{ errors.bank_branch_code }}</span>
            </div>

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
        business: Object,
        provider: Object,
        banks_list: Array,
        success_message: {
            type: String,
            default: ''
        }
    },
    data () {
        return {
            errors: {
                //
            },
            message: '',
            messageSuccess: false,
            form: {
                company_uen: '',
                company_name: '',
                bank_account_name: '',
                bank_swift_code: '',
                bank_branch_code: '',
                bank_account_no: '',
                bank_account_no_confirmation: '',
                password: '',
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
            }
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

                if (!this.form.bank_swift_code) {
                    this.errors.bank_swift_code = "Please select a bank.";
                }

                if (this.branches.length > 0 && !this.form.bank_branch_code) {
                    this.errors.bank_branch_code = "Please select the branch of the bank.";
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
    },

    watch : {
        'form.bank_swift_code' : {
            handler(value) {
                if (value) {
                    let bank = _.first(_.filter(this.banks_list, ({ swift_code }) => {
                        return swift_code === value;
                    }));

                    this.branches = bank.branches;
                    this.form.bank_branch_code = '';
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
