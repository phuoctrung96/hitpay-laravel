<template>
    <div class="card">
        <div class="card-body p-4">
            <h2>Update Bank Account</h2>
        </div>
        <div class="card-body p-4">
            <form id="business-bank-account" ref="businessBankAccount" @submit.prevent="updateBankAccount">
                <div id="group_currency" class="form-group">
                    <label class="col-form-label">Currency:</label>
                    <input id="currency" v-model="bank_account.currency.toUpperCase()" class="form-control" disabled>
                </div>
                <div id="group_bank_swift_code" class="form-group">
                    <label class="col-form-label">Select Bank:</label>
                    <div class="input-group">
                        <select v-model="bank_account.bank_swift_code" :class="getSelectClasses('bank_swift_code')" :disabled="is_updating">
                            <option value="" disabled>Please select a bank</option>
                            <option v-for="bank in banks" :value="bank.swift_code">{{ bank.name }}</option>
                        </select>
                    </div>
                    <span class="text-danger small" role="alert" v-if="errors.bank_swift_code">{{ errors.bank_swift_code }}</span>
                </div>
                <div id="group_branch" class="form-group">
                    <label class="col-form-label">Select Branch:</label>
                    <div class="input-group">
                        <select v-model="bank_account.branch_code" :class="getSelectClasses('branch_code')" :disabled="is_updating || !bank_account.bank_swift_code || branches.length <= 0">
                            <option value="" disabled>Please select a branch</option>
                            <option v-for="branch in branches" :value="branch.code">[{{ branch.code }}] {{ branch.name }}</option>
                        </select>
                    </div>
                    <span class="text-danger small" role="alert" v-if="errors.branch_code">{{ errors.branch_code }}</span>
                </div>
                <div id="group_number" class="form-group">
                    <label for="number">Account Number</label>
                    <input id="number" v-model="bank_account.number" :class="getInputClasses('number')" :disabled="is_updating">
                    <span class="invalid-feedback" role="alert" v-if="errors.number">{{ errors.number }}</span>
                </div>
                <div class="form-group">
                    <label for="number_confirmation">Confirm Account Number</label>
                    <input id="number_confirmation" v-model="number_confirmation" :class="getInputClasses('number_confirmation')" :disabled="is_updating">
                    <span class="invalid-feedback" role="alert" v-if="errors.number_confirmation">{{ errors.number_confirmation }}</span>
                </div>
                <div id="group_holder_type" class="form-group">
                    <label class="col-form-label">Account Holder Type</label>
                    <div class="input-group">
                        <select v-model="bank_account.holder_type" :class="getSelectClasses('holder_type')" :disabled="is_updating">
                            <option v-for="holder_type in holder_types" :value="holder_type.type">{{ holder_type.name }}</option>
                        </select>
                    </div>
                    <span class="text-danger small" role="alert" v-if="errors.holder_type">{{ errors.holder_type }}</span>
                </div>
                <div id="group_holder_name" class="form-group">
                    <label for="account_holder_name">Account Holder Name</label>
                    <input id="account_holder_name" v-model="bank_account.holder_name" :class="getInputClasses('holder_name')" :disabled="is_updating">
                    <span class="invalid-feedback" role="alert" v-if="errors.holder_name">{{ errors.holder_name }}</span>
                </div>
                <div v-if="!bank_account.use_in_hitpay || !bank_account.use_in_stripe" class="form-group">
                    <button v-if="!bank_account.use_in_hitpay" type="button" class="btn btn-sm btn-light" @click="openSetDefaultModal('hitpay')" :disabled="is_updating">
                        Set For HitPay
                    </button>
                    <button v-if="!bank_account.use_in_stripe" type="button" class="btn btn-sm btn-light" @click="openSetDefaultModal('stripe')" :disabled="is_updating">
                        Set For Stripe
                    </button>
                    <div class="modal fade" id="setDefaultModal" tabindex="-1" role="dialog" aria-labelledby="setDefaultModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="setDefaultModalLabel">Set Default</h5>
                                    <button id="setDefaultModalCloseBtn" type="button" class="close" data-dismiss="modal" aria-label="Close" :disabled="is_setting_default">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div v-if="is_default_set" class="modal-body">
                                    <p>{{ default_set_message }}</p>
                                    <a class="btn btn-primary" :href="getDomain(`business/${ this.business.id }/settings/bank-accounts/${ this.bank_account.id }/edit`, 'dashboard')">
                                        Done
                                    </a>
                                </div>
                                <div v-else class="modal-body">
                                    <p v-if="error_for_set_default" class="text-danger">{{ error_for_set_default }}</p>
                                    <div v-else>
                                        <p>{{ set_default_confirmation_message }}</p>
                                        <div class="text-right">
                                            <button id="setDefaultBtn" type="button" class="btn btn-danger" @click.prevent="setDefault" :disabled="is_setting_default">
                                                Confirm <i v-if="is_setting_default" class="fas fa-spinner fa-spin"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <p v-if="!canDelete()" class="small alert alert-warning mb-3">
                    <i class="fa fa-exclamation-triangle"></i> You can't delete this bank account because it is the default bank account being used for the payout of {{ payoutActivities() }}. Set another bank account as default before deleting this.
                </p>
                <div class="form-group mb-0">
                    <button type="submit" class="btn btn-primary" :disabled="is_updating">
                        Update <i v-if="is_updating" class="fas fa-circle-notch fa-spin"></i>
                    </button>
                    <button v-if="canDelete()" type="button" class="btn btn-link text-danger float-right" data-toggle="modal" data-target="#deleteModal" :disabled="is_updating">
                        Delete
                    </button>
                    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="deleteModalLabel">Delete</h5>
                                    <button id="closeBtn" type="button" class="close" data-dismiss="modal" aria-label="Close" :disabled="is_deleting">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div v-if="is_deleted" class="modal-body">
                                    The bank account has been deleted successfully.
                                    <div class="text-right">
                                        <a class="btn btn-primary" :href="getDomain(`business/${ this.business.id }/settings/bank-accounts`, 'dashboard')">
                                            Done
                                        </a>
                                    </div>
                                </div>
                                <div v-else class="modal-body">
                                    <p v-if="error_for_delete" class="text-danger">{{ error_for_delete }}</p>
                                    <p v-else>Are you sure you want to delete this bank account?</p>
                                    <div class="text-right">
                                        <button id="deleteBtn" type="button" class="btn btn-danger" @click.prevent="deleteBankAccount" :disabled="is_deleting">
                                            Confirm <i v-if="is_deleting" class="fas fa-spinner fa-spin"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
export default {
    name : "Components.Business.BankAccounts.Update",

    props : {
        business : {
            required : true,
            type : Object,
        },
        _bank_account : {
            required : true,
            type : Object,
        },
        banks : {
            required : true,
            type : Array,
        },
    },

    watch : {
        bank_account : {
            handler(value) {
                if (value.bank_swift_code) {
                    let bank = _.first(_.filter(this.banks, ({ swift_code }) => {
                        return swift_code === value.bank_swift_code;
                    }));

                    this.branches = bank.branches;
                } else {
                    this.branches = [];
                }
            },
            deep : true,
        },
    },

    data() {
        return {
            bank_account : {
                currency : this.business.currency,
                bank_swift_code : "",
                branch_code : "",
                number : "",
                holder_name : "",
                holder_type : "",
                use_in_hitpay : false,
                use_in_stripe : false,
                remark : "",
            },
            branches : {},
            default_set_message : null,
            error_for_delete : null,
            error_for_set_default : null,
            errors : {},
            holder_types : [
                {
                    type : "individual",
                    name : "Individual",
                },
                {
                    type : "company",
                    name : "Company",
                },
            ],
            is_default_set : false,
            is_deleted : false,
            is_deleting : false,
            is_setting_default : false,
            is_updating : false,
            number_confirmation : "",
            original_number : "",
            set_default_confirmation_message : null,
            set_default_for : null,
        };
    },

    mounted() {
        this.bank_account = this._bank_account;
        this.original_number = this.bank_account.number;
    },

    methods : {
        getInputClasses(field = null) {
            let classes = [ "form-control" ];

            if (field !== null) {
                if (this.errors[field]) {
                    classes.push("is-invalid");
                }
            }

            if (this.is_updating) {
                classes.push("bg-light");
            }

            return classes.join(" ");
        },

        getSelectClasses(field = null) {
            let classes = [ "custom-select" ];

            if (field !== null) {
                if (this.errors[field]) {
                    classes.push("is-invalid");
                }
            }

            if (this.is_updating) {
                classes.push("bg-light");
            }

            return classes.join(" ");
        },

        updateBankAccount() {
            this.is_updating = true;
            this.errors = {};

            if (!this.bank_account.bank_swift_code) {
                this.errors.bank_swift_code = "Please select a bank.";
            } else if (this.branches.length > 0 && !this.bank_account.branch_code) {
                this.errors.branch_code = "Please select the branch of the bank.";
            }

            if (this.bank_account.number.trim().length <= 0) {
                this.errors.number = "The account number is required.";
            } else if (!/(^[0-9]+$)+/.test(this.bank_account.number)) {
                this.errors.number = "The account number should contain only digits.";
            } else if (this.bank_account.number !== this.original_number && this.bank_account.number !== this.number_confirmation) {
                this.errors.number_confirmation = "Please confirm the account number.";
            }

            if (!this.bank_account.holder_type) {
                this.errors.holder_type = "Please select the account holder type.";
            }

            if (this.bank_account.holder_name.trim().length <= 0) {
                this.errors.holder_name = "The holder name is required.";
            } else if (!/(^[A-Za-z. ]+$)+/.test(this.bank_account.holder_name)) {
                this.errors.holder_name = "The holder name should contain only alphabet.";
            }

            if (Object.keys(this.errors).length > 0) {
                this.showError(_.first(Object.keys(this.errors)));

                return;
            }

            axios.put(
                this.getDomain(
                    `business/${ this.business.id }/settings/bank-accounts/${ this.bank_account.id }`,
                    "dashboard",
                ),
                this.bank_account,
            ).then(() => {
                window.location.href = this.getDomain(
                    `business/${ this.business.id }/settings/bank-accounts/${ this.bank_account.id }/edit`,
                    "dashboard",
                );
            }).catch(({ response }) => {
                if (response.status === 422) {
                    _.forEach(response.data.errors, (value, key) => {
                        this.errors[key] = _.first(value);
                    });

                    this.showError(_.first(Object.keys(this.errors)));
                }
            });
        },

        deleteBankAccount() {
            this.is_deleting = true;

            axios.delete(this.getDomain(
                `business/${ this.business.id }/settings/bank-accounts/${ this.bank_account.id }`,
                "dashboard",
            )).then(() => {
                this.is_deleted = true;
            }).catch(({ response }) => {
                this.error_for_delete = response.data.message;
                this.is_deleting = false;
            });
        },

        openSetDefaultModal(paymentProvider) {
            this.set_default_for = paymentProvider;

            if (paymentProvider === "hitpay") {
                paymentProvider = "HitPay";
            } else if (paymentProvider === "stripe") {
                paymentProvider = "Stripe";
            }

            this.set_default_confirmation_message = `Are you confirm you want to set this bank account as default for ${ paymentProvider } payout?`;

            $("#setDefaultModal").modal("show");
        },

        setDefault() {
            this.is_setting_default = true;

            axios.put(this.getDomain(
                `business/${ this.business.id }/settings/bank-accounts/${ this.bank_account.id }/for-${ this.set_default_for }`,
                "dashboard",
            )).then(({ data }) => {
                this.default_set_message = data.message;
                this.is_default_set = true;
            }).catch(({ response }) => {
                this.error_for_set_default = response.data.message;
                this.is_default_set = false;
            });
        },

        canDelete() {
            return !this.bank_account.use_in_hitpay && !this.bank_account.use_in_stripe;
        },

        payoutActivities() {
            let activities = [];

            if (this.bank_account.use_in_hitpay) {
                activities.push("HitPay");
            }

            if (this.bank_account.use_in_stripe) {
                activities.push("Stripe");
            }

            return activities.length > 0 ? activities.join(", ") : null;
        },

        showError(firstErrorKey) {
            if (firstErrorKey !== undefined) {
                this.scrollTo("#" + firstErrorKey);

                $("#" + firstErrorKey).focus();
            }

            this.is_updating = false;
        },
    },
};
</script>
