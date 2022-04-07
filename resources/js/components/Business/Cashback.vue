<style scoped>

.example-box {
    background-color: #d0dfec;
    border-radius: 10px;
}

.flexbox {
    display: flex;
    justify-content: center;
    flex-flow: column;
}
@media screen and (max-width: 768px) {
    .border-right{
        border-right: none!important;
        padding-right: unset!important;
    }
    .main-card {
        padding: 1.25rem !important;
    }
    .save-button{
        margin-left: 0!important;
        margin-right: 0!important;
        width: 100% !important;
    }
}
</style>
<template>
    <div class="card border-0 shadow-sm mx-4">
        <div class="card-body main-card px-5 py-3">
            <h4 class="text-primary mb-3 title">PayNow Cashback</h4>
            <small class="text-muted">Increase you PayNow adoption by offering cashback to customers paying with
                PayNow</small>
            <div class="row mt-4">
                <div class="col-md-7 col-sm-12">
                    <div class="pr-5 border-right">
                        <form>
                            <label>Cashback</label>
                            <div class="form-row mb-3">
                                <div class="col-5">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon3">%</span>
                                        </div>
                                        <input id="percentage"
                                               :class="{
                                            'is-invalid' : errors.percentage,
                                             }" v-model.number="cashback.percentage" class="form-control"
                                               aria-describedby="basic-addon3" @keypress="isNumber($event)">
                                    </div>
                                    <small class="text-muted">Percent</small>
                                    <span class="invalid-feedback d-block" role="alert" v-if="errors.percentage">{{ errors.percentage }}</span>
                                </div>
                                <div class="col-2 text-center align-self-center"><p class="mb-3">+</p></div>
                                <div class="col-5">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{{ businessCurrency }}</span>
                                        </div>
                                        <input id="fixed_amount"
                                               :class="{'is-invalid' : errors.fixed_amount,}"
                                               v-model.number="cashback.fixed_amount" class="form-control"
                                               aria-describedby="basic-addon3" @keypress="isNumber($event)">
                                    </div>
                                    <small class="text-muted">Fixed</small>
                                    <span class="invalid-feedback d-block" role="alert" v-if="errors.fixed_amount">{{ errors.fixed_amount }}</span>
                                </div>
                            </div>
                            <label>Minimum Amount</label>
                            <div class="form-row mb-3">
                                <div class="col-10">
                                    <div class="input-group ">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{{ businessCurrency }}</span>
                                        </div>
                                        <input v-model.number="cashback.minimum_order_amount"
                                               id="minimum_order_amount"
                                               :class="{'is-invalid' : errors.minimum_order_amount,}"
                                               class="form-control"
                                               aria-describedby="basic-addon3" @keypress="isNumber($event)">
                                    </div>
                                    <small class="text-muted">Min amount the cashback is applicable for (Mandatory Field)</small>
                                    <span class="invalid-feedback d-block" role="alert" v-if="errors.minimum_order_amount">{{ errors.minimum_order_amount }}</span>
                                </div>
                            </div>
                            <label>Maximum Cashback</label>
                            <div class="form-row mb-3">
                                <div class="col-10">
                                    <div class="input-group ">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">{{ businessCurrency }}</span>
                                        </div>
                                        <input v-model.number="cashback.maximum_cashback"
                                               id="maximum_amount"
                                               :class="{'is-invalid' : errors.maximum_cashback,}"
                                               class="form-control"
                                               aria-describedby="basic-addon3" @keypress="isNumber($event)">
                                    </div>
                                    <small class="text-muted">Cap limit of the cashback (Mandatory Field)</small>
                                    <span class="invalid-feedback d-block" role="alert" v-if="errors.maximum_cashback">{{ errors.maximum_cashback }}</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Payment Method</label>
                                <select class="form-control" id="payment_provider_charge_type" v-model="cashback.payment_provider_charge_type">
                                    <option v-for="method in payment_methods" :value="method[0]">{{
                                            method[1]
                                        }}
                                    </option>
                                </select>
                                <small class="text-muted">Currently only applicable for PayNow</small>
                                <span class="invalid-feedback d-block" role="alert" v-if="errors.payment_provider_charge_type">{{ errors.payment_provider_charge_type }}</span>
                            </div>
                            <div class="form-group">
                                <label>Channel</label>
                                <select class="form-control" id="channel" v-model="cashback.channel">
                                    <option v-for="channel in channels" :value="channel[0]">{{ channel[1] }}</option>
                                </select>
                                <span class="invalid-feedback d-block" role="alert" v-if="errors.channel">{{ errors.channel }}</span>
                            </div>
                            <div class="form-group">
                                <label class="d-block">End Date</label>
                                <datepicker input-class="form-control"
                                            id="ends_at"
                                            v-model="cashback.ends_at"
                                            :disabled-dates="disableDates"
                                            format="dd-MM-yyyy"></datepicker>
                                <a class="btn btn-danger" @click="clearDate">Clear</a>
                                <small class="text-muted d-block">Cashback will end after this date</small>
                                <span class="invalid-feedback d-block" role="alert" v-if="errors.ends_at">{{ errors.ends_at}}</span>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-md-5 col-sm-12 flexbox">
                    <div class="example-box py-5 px-4">
                        <h6 class="text-center mb-4">Example</h6>
                        <div class="form-group row mb-4">
                            <label class="col-6 col-form-label">
                                Charge Amount
                            </label>
                            <div class="col-6">
                                <input type="number" v-model="example.amount" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <small class="col-8 text-muted">
                                HitPay transaction fee
                            </small>
                            <span class="col-4">
                                {{ transactionFee }}
                            </span>
                        </div>
                        <div class="row">
                            <small class="col-8 text-muted">
                                Bank Transfer Fee (Waived off)
                            </small>
                            <span class="col-4">
                                {{ cashbackAdminFee }}
                            </span>
                        </div>
                        <div class="row">
                            <small class="col-8 text-muted">
                                Cashback to customer
                            </small>
                            <span class="col-4">
                                {{ customerCashback }}
                            </span>
                        </div>
                        <div class="row">
                            <small class="col-8 text-muted">
                                Net amount received
                            </small>
                            <span class="col-4">
                                {{ netAmount }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-7 col-sm-12">
                    <button class="btn btn-primary save-button text-uppercase shadow mt-4 d-block w-75 mx-5" @click="saveCashback()"
                            :disabled="is_processing">
                        Save
                        <i v-if="is_processing" class="fas fa-spinner fa-spin"></i>
                    </button>
                    <div class="text-center mt-2">
                        <small class="text-muted ">SGD 0.50 Bank transfer Fee Waived off until end of 2021</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Datepicker from 'vuejs-datepicker';

export default {
    name: "Cashback",

    components: {
        Datepicker
    },
    data() {
        return {
            business: [],
            channels: [],
            payment_methods: [],
            is_processing: false,
            errors: {},
            cashback: {
                percentage: 0,
                fixed_amount: 0,
                minimum_order_amount: 0,
                maximum_cashback: 0,
                payment_provider_charge_type: '',
                channel: '',
                ends_at: ''
            },
            example: {
                amount: 0,
            },
            fee: {
                paynow_fee_percent: 0.8,
                paynow_fee_fixed: 0.3,
                cashback_admin_fee: 0.50,
            }
        }
    },

    mounted() {
        this.business = Business;
        this.channels = Object.entries(Channels);
        this.payment_methods = Object.entries(PaymentMethods);

        if (Cashback){
            this.cashback = Cashback;
            if (Cashback.ends_at)
                this.cashback.ends_at = new Date(Cashback.ends_at);
            this.cashback.fixed_amount = (this.cashback.fixed_amount / 100);
            this.cashback.minimum_order_amount = (this.cashback.minimum_order_amount / 100);
            this.cashback.maximum_cashback = (this.cashback.maximum_cashback / 100);
        }
        if (Fees){
            this.fee.cashback_admin_fee = Fees.cashback_admin_fee;
        }
    },
    methods: {
        saveCashback() {
            this.errors = {};
            this.is_processing = true;
            if (this.cashback.percentage === 0 && this.cashback.fixed_amount === '')
            {
                this.errors.percentage = 'Either percent or fixed value is required'
            }
            if (this.cashback.percentage === '' && this.cashback.fixed_amount === 0)
            {
                this.errors.percentage = 'Either percent or fixed value is required'
            }
            if (this.cashback.percentage === 0 && this.cashback.fixed_amount === 0)
            {
                this.errors.percentage = 'Either percent or fixed value is required'
            }
            if (this.cashback.percentage === '' && this.cashback.fixed_amount === '')
            {
                this.errors.percentage = 'Either percent or fixed value is required'
            }
            if (this.cashback.minimum_order_amount === 0 || this.cashback.minimum_order_amount === '' )
            {
                this.errors.minimum_order_amount = 'Minimum amount is required'
            }
            if (this.cashback.maximum_cashback === 0 || this.cashback.maximum_cashback === '')
            {
                this.errors.maximum_cashback = 'Maximum cashback is required'
            }
            if (this.cashback.payment_provider_charge_type === '')
            {
                this.errors.payment_provider_charge_type = 'Please choose payment method'
            }
            if (this.cashback.channel === '')
            {
                this.errors.channel = 'Please choose channel'
            }

            let totalCashBack = this.cashback.minimum_order_amount * this.cashback.percentage / 100 + this.cashback.fixed_amount;
            if (totalCashBack > this.cashback.minimum_order_amount && this.cashback.percentage > 100) {
                this.errors.percentage = 'Cashback value is greater than minimum amount';
            }
            if (totalCashBack > this.cashback.minimum_order_amount && this.cashback.fixed_amount > this.cashback.minimum_order_amount) {
                this.errors.fixed_amount = 'Cashback value is greater than minimum amount';
            }
            if (totalCashBack > this.cashback.minimum_order_amount && 
                (this.cashback.percentage > 0 && this.cashback.percentage <= 100) &&
                (this.cashback.fixed_amount > 0 && this.cashback.fixed_amount < this.cashback.minimum_order_amount) ) {
                this.errors.minimum_order_amount = 'Minimum amount is less than percent + fixed cashback value';
                this.errors.percentage = ' ';
                this.errors.fixed_amount = ' ';
            }

            if (Object.keys(this.errors).length > 0) {
                this.showError(_.first(Object.keys(this.errors)));
            }
            else {
                if(this.cashback.ends_at)
                    this.cashback.ends_at = this.getEndDate();
                if (this.cashback.fixed_amount === null || this.cashback.fixed_amount === '') this.cashback.fixed_amount = 0;
                if (this.cashback.percentage === null || this.cashback.percentage === '') this.cashback.percentage = 0;

                axios.post(this.getDomain('business/' + this.business.id + '/cashback', 'dashboard'), this.cashback).then(({data}) => {
                    window.location.href = data.redirect_url;
                });
            }
        },
        showError(firstErrorKey) {
            if (firstErrorKey !== undefined) {
                this.scrollTo('#' + firstErrorKey);

                $('#' + firstErrorKey).focus();
            }
            this.is_processing = false;
        },
        getEndDate(){
            let fromMonth = this.cashback.ends_at.getMonth()+1;

            if (fromMonth < 10) {
                fromMonth = '0' + fromMonth;
            }

            let fromDay = this.cashback.ends_at.getDate();

            if (fromDay < 10) {
                fromDay = '0' + fromDay;
            }

            return this.cashback.ends_at.getFullYear() + '-' + fromMonth + '-' + fromDay;
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
        clearDate(){
            this.cashback.ends_at = "";
        }
    },
    computed: {
        businessCurrency() {
            return Business.currency.toUpperCase();
        },
        disableDates() {
            var date = new Date();
            date.setDate(date.getDate() - 1);
            return {
                to: date
            }
        },
        transactionFee() {
            return (this.example.amount * this.fee.paynow_fee_percent / 100 + this.fee.paynow_fee_fixed).toFixed(2);
        },
        customerCashback() {
            let cashback;
            if (this.cashback.percentage && this.cashback.fixed_amount) {
                cashback = (this.example.amount * this.cashback.percentage / 100 + this.cashback.fixed_amount).toFixed(2);
            }
            else if (!this.cashback.fixed_amount && this.cashback.percentage){
                cashback = (this.example.amount * this.cashback.percentage / 100).toFixed(2);
            }
            else if (!this.cashback.percentage && this.cashback.fixed_amount){
                cashback = (this.cashback.fixed_amount).toFixed(2);
            }
            else cashback = 0;
            if (this.cashback.minimum_order_amount) {
                if (this.example.amount < this.cashback.minimum_order_amount) {
                    return 0;
                }
            }
            if (this.cashback.maximum_cashback)
                if (cashback > this.cashback.maximum_cashback) return (this.cashback.maximum_cashback).toFixed(2);
            return cashback;
        },
        cashbackAdminFee(){
            if (this.cashback.minimum_order_amount) {
                if (this.example.amount < this.cashback.minimum_order_amount) {
                    return 0;
                }
            }
            return (this.fee.cashback_admin_fee).toFixed(2)
        },
        netAmount() {
            if (this.example.amount == 0) return 0.00;

            return (this.example.amount - this.transactionFee - this.customerCashback - this.cashbackAdminFee).toFixed(2);
        }
    }
}
</script>
