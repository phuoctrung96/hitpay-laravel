<template>
    <div class="card">
        <div class="card-body p-4">
            <h2>Create Bank Account</h2>
        </div>
        <div class="card-body p-4">
            <form id="business-bank-account" ref="businessBankAccount" @submit.prevent="store">
                <div id="group_currency" class="form-group">
                    <label class="col-form-label">Currency:</label>
                    <input id="currency" v-model="bank_account.currency.toUpperCase()" class="form-control" disabled>
                </div>
                <div id="group_bank_swift_code" class="form-group">
                    <label class="col-form-label">Select Bank:</label>
                    <div class="input-group">
                        <select v-model="bank_account.bank_id" :class="getSelectClasses('bank_id')" :disabled="is_creating">
                            <option value="" disabled>Please select a bank</option>
                            <option v-for="bank in banks" :value="bank.id">{{ bank.name_code }}</option>
                        </select>
                    </div>
                    <span class="text-danger small" role="alert" v-if="errors.bank_id">{{ errors.bank_id }}</span>
                </div>
                <div id="group_branch" class="form-group">
                    <label class="col-form-label">Select Branch:</label>
                    <div class="input-group">
                        <select v-model="bank_account.branch_code" :class="getSelectClasses('branch_code')" :disabled="is_creating || !bank_account.bank_id || branches.length <= 0">
                            <option value="" disabled>Please select a branch</option>
                            <option v-for="branch in branches" :value="branch.code">[{{ branch.code }}] {{ branch.name }}</option>
                        </select>
                    </div>
                    <span class="text-danger small" role="alert" v-if="errors.branch_code">{{ errors.branch_code }}</span>
                </div>
                <div id="group_number" class="form-group">
                    <label for="number">Account Number</label>
                    <input id="number" v-model="bank_account.number" :class="getInputClasses('number')" :disabled="is_creating">
                    <span class="invalid-feedback" role="alert" v-if="errors.number">{{ errors.number }}</span>
                </div>
                <div class="form-group">
                    <label for="number_confirmation">Confirm Account Number</label>
                    <input id="number_confirmation" v-model="number_confirmation" :class="getInputClasses('number_confirmation')" :disabled="is_creating">
                    <span class="invalid-feedback" role="alert" v-if="errors.number_confirmation">{{ errors.number_confirmation }}</span>
                </div>
                <div id="group_holder_type" class="form-group">
                    <label class="col-form-label">Account Holder Type</label>
                    <div class="input-group">
                        <select v-model="bank_account.holder_type" :class="getSelectClasses('holder_type')" :disabled="is_creating">
                            <option v-for="holder_type in holder_types" :value="holder_type.type">{{ holder_type.name }}</option>
                        </select>
                    </div>
                    <span class="text-danger small" role="alert" v-if="errors.holder_type">{{ errors.holder_type }}</span>
                </div>
                <div id="group_holder_name" class="form-group">
                    <label for="account_holder_name">Account Holder Name</label>
                    <input id="account_holder_name" v-model="bank_account.holder_name" :class="getInputClasses('holder_name')" :disabled="is_creating">
                    <span class="invalid-feedback" role="alert" v-if="errors.holder_name">{{ errors.holder_name }}</span>
                </div>
                <div id="group_use_in_stripe" class="form-group">
                    <label>Use This Bank Account For</label>
                    <div class="form-check">
                        <input type="checkbox" id="use_in_hitpay" class="form-check-input" title="" v-model="bank_account.use_in_hitpay" :disabled="is_creating || bank_accounts_count === 0">
                        <label class="form-check-label" for="use_in_hitpay">HitPay</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" id="use_in_stripe" class="form-check-input" title="" v-model="bank_account.use_in_stripe" :disabled="is_creating || bank_accounts_count === 0">
                        <label class="form-check-label" for="use_in_stripe">Stripe</label>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary" :disabled="is_creating">
                        Create <i v-if="is_creating" class="fas fa-circle-notch fa-spin"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
export default {
    name : "Components.Business.BankAccounts.Create",

    props : {
        business : {
            required : true,
            type : Object,
        },
        bank_accounts_count : {
            type : Number,
        },
        banks : {
            required : true,
            type : Array,
        },
    },

    watch : {
        bank_account : {
            handler(value) {
                if (value.bank_id) {
                    let bank = _.first(_.filter(this.banks, ({ id }) => {
                        return id === value.bank_id;
                    }));

                    this.branches = bank.branches;
                }
            },
            deep : true,
        },
    },

    data() {
        return {
            bank_account : {
                currency : this.business.currency,
                bank_id : "",
                branch_code : "",
                number : "",
                holder_name : "",
                holder_type : "",
                use_in_hitpay : false,
                use_in_stripe : false,
                remark : "",
            },
            branches : {},
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
            is_creating : false,
            number_confirmation : "",
        };
    },

    mounted() {
        if (this.business.business_type === 'partner' && this.business.country === 'sg') {
            this.bank_account.use_in_hitpay = true;
            this.bank_account.use_in_stripe = false;
        } else {
            if (this.bank_accounts_count === 0) {
                this.bank_account.use_in_hitpay = true;
                this.bank_account.use_in_stripe = true;
            }
        }
    },

    methods : {
        getInputClasses(field = null) {
            let classes = [ "form-control" ];

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

        store() {
            this.is_creating = true;
            this.errors = {};

            if (!this.bank_account.bank_id) {
                this.errors.bank_id = "Please select a bank.";
            } else if (this.branches.length > 0 && !this.bank_account.branch_code) {
                this.errors.branch_code = "Please select the branch of the bank.";
            }

            if (this.bank_account.number.trim().length <= 0) {
                this.errors.number = "The account number is required.";
            } else if (!/(^[0-9]+$)+/.test(this.bank_account.number)) {
                this.errors.number = "The account number should contain only digits.";
            } else if (this.bank_account.number !== this.number_confirmation) {
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

            axios.post(
                this.getDomain(`business/${ this.business.id }/settings/bank-accounts`, "dashboard"),
                this.bank_account,
            ).then(({ data }) => {
                window.location.href = this.getDomain(
                    `business/${ this.business.id }/settings/bank-accounts/${ data.id }/edit`, "dashboard");
            }).catch(({ response }) => {
                if (response.status === 422) {
                    _.forEach(response.data.errors, (value, key) => {
                        this.errors[key] = _.first(value);
                    });

                    this.showError(_.first(Object.keys(this.errors)));
                }
            });
        },

        showError(firstErrorKey) {
            if (firstErrorKey !== undefined) {
                this.scrollTo("#" + firstErrorKey);

                $("#" + firstErrorKey).focus();
            }

            this.is_creating = false;
        },
    },
};
</script>
