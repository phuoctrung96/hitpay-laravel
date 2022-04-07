<template>
    <div>
        <div class="card-body p-4">
            <h2 v-if="!is_updating" class="text-primary mb-0 title">Create campaign</h2>
            <h2 v-else class="text-primary mb-0 title">Edit campaign</h2>
        </div>
        <div class="card-body bg-light border-top ">
            <div class="form-row">
                <div class="col-6 mb-3">
                    <label for="name" class="small text-muted text-uppercase">Name of campaign</label>
                    <input id="name" type="text" class="form-control" title="" v-model="campaign.name" :class="{
                        'is-invalid' : errors.name
                    }" :disabled="is_processing">
                    <span class="invalid-feedback" role="alert">{{ errors.name }}</span>
                </div>
            </div>
            <div class="form-row">
                <div class="col-6 mb-3">
                    <label for="business_id" class="small text-muted text-uppercase">Enter business id</label>
                    <input id="business_id" class="form-control" title="" v-model="campaign.campaign_business_id"
                           :class="{
                    'is-invalid' : errors.campaign_business_id
                }" :disabled="is_processing">
                    <span class="invalid-feedback" role="alert">{{ errors.campaign_business_id }}</span>
                </div>
            </div>
            <div class="form-row">
                <div class="col-6 mb-3">
                    <label for="fund" class="small text-muted text-uppercase">Initial fund</label>
                    <input id="fund" type="number" class="form-control" title="" v-model="campaign.fund" :class="{
                        'is-invalid' : errors.fund
                    }" :disabled="is_processing">
                    <span class="invalid-feedback" role="alert">{{ errors.fund }}</span>
                </div>
            </div>
            <div class="form-row">
                <div class="col-6 mb-3">
                    <label for="payment_method" class="small text-muted text-uppercase">Payment Method</label>
                    <select id="payment_method" class="form-control" title="" v-model="campaign.payment_method" :class="{
                        'is-invalid' : errors.payment_method
                    }" :disabled="is_processing">
                        <option v-for="method in payment_methods" :value="method">{{method}}</option>
                    </select>
                    <span class="invalid-feedback" role="alert">{{ errors.payment_method }}</span>
                </div>
                <div class="col-6 mb-3">
                    <label for="payment_sender" class="small text-muted text-uppercase">Payment Sender</label>
                    <input id="payment_sender" type="text" class="form-control" title=""
                           v-model="campaign.payment_sender" :class="{
                        'is-invalid' : errors.payment_sender
                    }" :disabled="is_processing">
                    <span class="invalid-feedback" role="alert">{{ errors.payment_sender }}</span>
                </div>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" v-model="campaign.status" id="status">
                <label class="form-check-label" for="status">
                    Enabled
                </label>
            </div>
            <hr>
            <AddRulesCampaign
                @addRule="rules.push($event)"/>
            <span v-if="errors.rules" class="invalid-feedback d-block" role="alert">{{ errors.rules }}</span>
            <table v-if="rules.length" class="table">
                    <thead>
                    <tr>
                        <th scope="col">Business Id</th>
                        <th scope="col">Currency</th>
                        <th scope="col">Minimum amount</th>
                        <th scope="col">Fixed</th>
                        <th scope="col">Percent</th>
                        <th scope="col">Max cashback</th>
                        <th scope="col">Total cashback</th>
                        <th scope="col">Balance cashback</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(rule,index) in rules">
                        <td>{{rule.business_id}}</td>
                        <td>{{rule.currency}}</td>
                        <td>{{rule.min_spend}}</td>
                        <td>{{rule.cashback_amt_fixed}}</td>
                        <td>{{rule.cashback_amt_percent}}</td>
                        <td>{{rule.maximum_cap}}</td>
                        <td>{{rule.total_cashback}}</td>
                        <td>{{rule.balance_cashback}}</td>
                        <td><i class="fa fa-trash" aria-hidden="true" style="color: red" @click="rules.splice(index, 1);"></i></td>
                    </tr>
                    </tbody>
            </table>
            <button class="btn btn-primary btn-block" @click.prevent="saveMethod" :disabled="is_processing">
                <template v-if="is_updating">
                    <i class="fas fa-save mr-3"></i> Save
                </template>
                <template v-else>
                    <i class="fas fa-plus mr-3"></i> Add Campaign
                </template>
                <i v-if="is_processing" class="fas fa-spinner fa-spin"></i>
            </button>
            <button v-if="is_updating" class="btn btn-danger btn-block" @click="deleteMethod">Delete</button>
        </div>
    </div>
</template>
<script>
import AddRulesCampaign from "../Admin/AddRulesCampaign";

export default {
    components: {
        AddRulesCampaign
    },
    data() {
        return {
            errors: {},
            is_processing: false,
            is_updating: false,
            campaign: {
                name: '',
                campaign_business_id: '',
                fund: 0,
                status: true,
                payment_method: '',
                payment_sender: '',
            },
            rules: [],
            payment_methods: []
        }
    },

    mounted() {
        if (Campaign) {
            this.campaign = Campaign;
            this.rules = Campaign.rules;

            this.is_updating = true;
        }
        if (PaymentMethods) {
            this.payment_methods = PaymentMethods;
        }
    },


    methods: {
        saveMethod() {
            this.is_processing = true;

            this.errors = {};

            if (this.campaign.name === '') {
                this.errors.campaign_business_id = 'The name of campaign is required.';
            }

            if (this.campaign.campaign_business_id === '') {
                this.errors.campaign_business_id = 'The campaign business id is required.';
            }

            if (this.campaign.payment_method === '') {
                this.errors.payment_method = 'The payment method is required.';
            }

            if (this.campaign.fund <= 0) {
                this.errors.fund = 'The initial fund can\'t be lower than 1';
            }

            if (!this.rules.length) {
                this.errors.rules = 'At least 1 rule is required';
            }

            if (Object.keys(this.errors).length > 0) {
                this.showError(_.first(Object.keys(this.errors)));
                return;
            }

            let form = {
                'campaign' : this.campaign,
                'rules' : this.rules
            }

            if (this.is_updating) {
                axios.post(this.getDomain('campaigns/' + this.campaign.id, 'admin'), form).then(({data}) => {
                    window.location.href = data.redirect_url;
                }).catch(({response}) => {
                    if (response.status === 422) {
                        _.forEach(response.data.errors, (value, key) => {
                            this.errors[key] = _.first(value);
                        });

                        this.showError(_.first(Object.keys(this.errors)));
                    }
                });
            } else {
                axios.post(this.getDomain('campaigns', 'admin'), form).then(({data}) => {
                    window.location.href = data.redirect_url;
                }).catch(({response}) => {
                    if (response.status === 422) {
                        _.forEach(response.data.errors, (value, key) => {
                            this.errors[key] = _.first(value);
                        });

                        this.showError(_.first(Object.keys(this.errors)));
                    }
                });
            }
        },

        deleteMethod() {
            this.is_processing = true;

            axios.delete(this.getDomain('campaigns/' + this.campaign.id, 'admin')).then(({data}) => {
                window.location.href = data.redirect_url;
            }).catch(({response}) => {
                if (response.status === 403) {
                    this.is_processing = false;
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
    },
}
</script>
