<style scoped>
    .save-button{
        align-self: center;
        width: 200px;
        font-size: 16px !important;
    }
</style>
<template>
    <div>
        <button class="btn btn-primary" data-toggle="modal"
                data-target="#paymentLinkModal">
            <i class="fas fa-plus mr-2"></i> Create Payment Link
        </button>
        <div class="modal fade" id="paymentLinkModal" tabindex="-1" role="dialog"
             aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button id="closeBtn" type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-4">
                        <form>
                            <div class="px-4 mb-3 pt-3">
                                <label for="currency_list">Select Currency </label>
                                <select id="currency_list" @change="checkDecimal"
                                    class="form-control form-control-sm"
                                    v-model="payment_link.currency"
                                    :class="{'is-invalid' : errors.currency && payment_link.currency == ''}"
                                    :disabled="is_processing"
                                    :selected="payment_link.currency">
                                    <option value="" disabled>Select Currency</option>
                                    <option v-for="(value, key) in currency_list" :value="key">
                                        {{ value.toUpperCase() }}
                                    </option>
                                </select>
                                <span v-if="typeof errors.currency == 'string'" class="invalid-feedback"
                                      role="alert">
                    {{ errors.currency }}
                </span>
                            </div>
                            <div class="px-4 mb-3 border-top pt-3">
                                <label>Amount </label>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend">
                                            <span class="input-group-text"
                                                  id="basic-addon3">{{ payment_link.currency}}</span>
                                    </div>
                                    <input v-model="payment_link.amount" @change="checkDecimal" @keypress="isNumber($event)"
                                           :class="{ 'is-invalid' : errors.amount }" class="form-control"
                                           placeholder="$250" step="0.01" title="Amount">
                                    <span class="invalid-feedback" role="alert"
                                          v-if="errors.amount">{{ errors.amount }}</span>
                                </div>
                            </div>
                            <div class="px-4 mb-3 border-top pt-3">
                                <label>Email</label>
                                <input type="text" v-model="payment_link.email"
                                       :class="{ 'is-invalid' : errors.email }"
                                       class="form-control form-control-sm w-75">
                                <span class="small text-muted">optional</span>
                                <span class="invalid-feedback" role="alert"
                                      v-if="errors.email">{{ errors.email }}</span>
                            </div>
                            <div class="px-4 mb-3 border-top pt-3">
                                <PhoneInput
                                    id="phone_number"
                                    @phoneInput="savePhone"
                                    label="Phone Number (optional)"
                                    ref="phoneInput"
                                    :error="errors.phone_number"
                                    :disabled="is_processing"
                                />
                            </div>
                            <div class="px-4 mb-3 border-top pt-3">
                                <label>Reference</label>
                                <input type="text" v-model="payment_link.reference_number"
                                       :class="{ 'is-invalid' : errors.reference_number }"
                                       class="form-control form-control-sm w-75">
                                <span class="small text-muted">optional</span>
                                <span class="invalid-feedback" role="alert"
                                      v-if="errors.reference_number">{{ errors.reference_number }}</span>
                            </div>
                            <div class="px-4 mb-3 border-top pt-3">
                                <label class="d-block">Expiry Date</label>
                                <datepicker id="expiry_date" v-model="payment_link.expiry_date" :disabled-dates="disableDates"
                                            :bootstrap-styling="true" placeholder="Click here to select date"
                                            :format="'yyyy-MM-dd'" :class="{
                    'is-invalid' : errors.expiry_date
                }"></datepicker>
                                <span class="small text-mute d-block">optional</span>
                                <span class="invalid-feedback" role="alert"
                                      v-if="errors.expiry_date">{{ errors.expiry_date }}</span>
                            </div>
                            <div class="border-top pt-3 mb-2">
                                <CustomisationCheckBox
                                    v-model="payment_link.repeated"
                                    :name="'Allow repeated payments'"
                                />
                            </div>
                        </form>
                        <div class="d-flex justify-content-center">
                            <CheckoutButton
                                class="save-button mt-4"
                                title="Create Link"
                                :disabled="is_processing"
                                @click="saveMethod"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="statusModal" class="modal" tabindex="-1" role="dialog" data-backdrop="static">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Succeeded
                        </h5>
                    </div>
                    <div class="modal-body bg-light text-center">
                        <h3 class="mb-3">Done!</h3>
                        <p><i class="fas fa-check-circle fa-3x text-success"></i></p>
                        <p>Payment link created.</p>
                        <p class="h3 mb-3">{{ payment_link.currency.toUpperCase() }}{{ payment_link.amount }}</p>
                        <div class="input-group">
                            <input type="hidden" id="payment_link" :value="generated_payment_link">
                            <b class="mb-2"><a :href="generated_payment_link">{{ generated_payment_link }}</a></b>
                            <button v-if="!is_payment_link_copied" class="btn btn-primary btn-block" @click="copy">Copy</button>
                            <button v-if="is_payment_link_copied" class="btn btn-primary btn-block" @click="copy">Copied!</button>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <a data-dismiss="modal" href="#" @click="clearData">Close</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Datepicker from "vuejs-datepicker";
import CustomisationCheckBox from '../Dashboard/CheckoutCustomization/CustomisationCheckBox'
import MethodsSelector from '../Dashboard/CheckoutCustomization/MethodsSelector'
import CheckoutButton from '../Shop/CheckoutButton'
import PhoneInput from '../Authentication/PhoneInput'


export default {
    name: "PaymentLinkCreate",
    components: {
        Datepicker,
        CustomisationCheckBox,
        MethodsSelector,
        CheckoutButton,
        PhoneInput,
    },
    props: {
        currency_list: {
            type: Object,
            required: true
        },
        zero_decimal_list: {
            type: Array,
        },
    },
    data() {
        return {
            errors: {},
            is_processing: false,
            is_payment_link_copied: false,
            generated_payment_link: '',
            payment_link: {
                currency: 'SGD',
                amount: 0,
                email: '',
                full_phone_number: '',
                phone_number: '',
                phone_dial_code: '',
                reference_number: '',
                expiry_date: '',
                repeated: false
            },
        }

    },
    methods: {
        checkDecimal() {
            if (this.payment_link.amount !== '' && this.zero_decimal_list.includes(this.payment_link.currency)) {
                if (/.*\.|,.*/.test(this.payment_link.amount)) {
                    this.errors = {
                        amount: this.payment_link.currency + ' is zero-decimal currency',
                    };
                }
            }
        },

        checkRequired() {
            if (!this.payment_link.currency) {
                this.errors = {
                    currency: true,
                };
            }
            if (!this.payment_link.amount || !(this.payment_link.amount > 0)) {
                this.errors = {
                    amount: 'Please enter amount > 0',
                };
            }

            if (this.payment_link.email && !(/\S+@\S+\.\S+/.test(this.payment_link.email)))
                this.errors.email = 'Invalid email format';
        },

        getTime(time) {
            if (typeof time != 'string') {
                let date = time.getDate() + '';
                if (date.length === 1) {
                    date = '0' + date;
                }
                let month = (time.getMonth() + 1) + '';
                if (month.length === 1) {
                    month = '0' + month;
                }

                return time.getFullYear()+'-'+month+'-'+date;
            }

            return time;
        },

        saveMethod() {
            this.is_processing = true;

            this.errors = {
                //
            };

            this.checkDecimal()
            this.checkRequired()

            if(Number.parseInt(this.payment_link.amount) < 0 || Number.parseInt(this.payment_link.amount) > 9999999){
                this.errors.amount = "Invalid amount, must be more than 0 and less than 9999999.";
            }

            if (this.payment_link.phone_number.length > 15) {
                this.errors.phone_number = 'The phone number may not be greater than 15 characters.';
            }

            if (this.payment_link.phone_number.length > 0 && this.payment_link.phone_number.length < 8) {
                this.errors.phone_number = 'The phone number may not be less than 8 characters.';
            }

            if (Object.keys(this.errors).length > 0) {
                this.showError(_.first(Object.keys(this.errors)));
                return;
            }

            if (this.payment_link.expiry_date !== '')
                this.payment_link.expiry_date = this.getTime(this.payment_link.expiry_date);

            this.payment_link.full_phone_number = this.phoneNumber;

            axios.post(this.getDomain('business/' + Business.id + '/payment-links', 'dashboard'), this.payment_link)
                .then(({data}) => {
                    this.generated_payment_link = data.payment_link;
                    this.is_processing = false;

                    $("#paymentLinkModal").modal('hide');
                    $("#statusModal").modal('show');
                }).catch(({response}) => {
                if (response.status === 422) {
                    _.forEach(response.data.errors, (value, key) => {
                        if (key === 'full_phone_number') {
                            this.errors['phone_number'] = _.first(value);
                        } else {
                            this.errors[key] = _.first(value);
                        }
                    });

                    this.showError(_.first(Object.keys(this.errors)));
                }
            });
        },

        showError(firstErrorKey) {
            if (firstErrorKey !== undefined) {
                this.scrollTo('#' + firstErrorKey);

                $('#' + firstErrorKey).focus();
            }

            this.is_processing = false;
        },
        isNumber(evt) {
            evt = (evt) ? evt : window.event;
            var charCode = (evt.which) ? evt.which : evt.keyCode;
            if ((charCode > 31 && (charCode < 48 || charCode > 57)) && charCode !== 46) {
                evt.preventDefault();;
            } else {
                return true;
            }
        },
        copy() {
            let link = document.querySelector('#payment_link')
            link.setAttribute('type', 'text')
            link.select()

            let $this = this;

            try {
                let copied = document.execCommand('copy');
                if (copied) {
                    $this.is_payment_link_copied = true;
                }
            } catch (err) {
                alert('Oops, unable to copy');
            }

            /* unselect the range */
            link.setAttribute('type', 'hidden')
            window.getSelection().removeAllRanges()

            setTimeout(function () {
                $this.is_payment_link_copied = false;
            }, 3000)
        },
        clearData(){
            this.payment_link = {
                currency: window.Business.currency.toUpperCase(),
                amount: 0,
                email: '',
                phone_number: '',
                phone_dial_code: '',
                full_phone_number: '',
                reference_number: '',
                expiry_date: '',
                repeated: false,
            };
        },

        savePhone(event, phone, phone_dial_code) {
            this.payment_link.phone_number = phone;
            this.payment_link.phone_dial_code = phone_dial_code;
        },
    },
    computed: {
        disableDates() {
            var date = new Date();
            date.setDate(date.getDate() - 1);
            return {
                to: date
            }
        },

        phoneNumber() {
            return this.payment_link.phone_dial_code + this.payment_link.phone_number;
        }
    },

    mounted() {
        if (window.Business) {
            this.payment_link.currency = window.Business.currency.toUpperCase();

            this.$refs.phoneInput.applyCountry(window.Business.country);
        }
    }
}
</script>
