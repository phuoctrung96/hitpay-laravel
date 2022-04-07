<template>
    <div>
        <div class="modal fade show" id="exportModal" tabindex="-1" role="dialog" aria-labelledby="exportModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exportModalLabel">Complete Payment Gateway Setup with Xero</h5>
                        <button id="closeBtn" type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="model-body">
                        <div class="container">
                            <div class="row">
                                <div class="col-md-12 pb-4">
                                    <p class="pt-2">Enable payment methods for the HitPay payment gateway on Xero by clicking on the button below</p>
                                    <a href="#" @click="gatewaySettingsRedirect" class="btn btn-primary text-white">Enable</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h2 class="text-primary mb-3 title">Xero Settings</h2>
                <p>Data will be automatically synced to your connected Xero Account at 6:00 am SGT everyday. Only transaction data in SGD will be imported</p>
                <p>Connected Email Address: {{email}}</p>
                <p>Connected Xero Organization: {{organization}}</p>
                <p class="alert alert-info">A HitPay Clearing Account is automatically created in your Xero account upon completion of settings below.</p>
                <button class="btn"
                    @click="disconnectXeroAccount"
                >
                    <img src="/images/disconnect-blue.svg" alt="">
                </button>
            </div>
            <div class="card-body border-top">
                <div class="form-group" v-if="!-setting.disable_sales_feed">
                    <label for="sync_date">Select Sync Start Date <span class="text-danger">*</span></label>
                    <datepicker v-model="setting.sync_date" name="sync_date" id="sync_date"></datepicker>
                    <span v-if="errors.sync_date" class="invalid-feedback" role="alert">{{ errors.sync_date }}</span>
                </div>
                <div class="form-group">
                    <label for="paynow_btn_text">Xero PayNow button text <span class="text-danger">*</span></label>
                    <input maxlength="30" class="form-control" type="text" v-model="setting.paynow_btn_text" name="paynow_btn_text" id="paynow_btn_text" />
                    <span v-if="errors.paynow_btn_text" class="invalid-feedback" role="alert">{{ errors.paynow_btn_text }}</span>
                </div>
                <div class="form-group">
                    <label for="sales_account"> Select Xero branding theme <span class="text-danger">*</span></label>
                    <select id="xero_branding_theme" class="form-control" v-model="setting.xero_branding_theme">
                        <option v-for="(theme, index) in xero_branding_themes" :value="index">{{theme}}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="bank_xero_account"> Select Xero Bank Account For Payout <span class="text-danger">*</span></label>
                    <select id="bank_xero_account" class="form-control" v-model="setting.xero_payout_account_id">
                        <option v-for="(account) in bank_accounts" :value="account.id">{{account.name}}</option>
                    </select>
                    <span v-if="errors.xero_payout_account_id_text" class="invalid-feedback" role="alert">{{ errors.xero_payout_account_id_text }}</span>
                </div>

                <div class="form-group d-none">
                    <label for="sales_account"> Select Account Type For Sales Data Import <span class="text-danger">*</span></label>
                    <select id="sales_account" class="form-control" v-model="setting.sales_account_type">
                        <option v-for="(account, index) in xero_account_types" :value="index">{{account}}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="sales_xero_account"> Select Xero Account For Sales <span class="text-danger">*</span></label>
                    <select id="sales_xero_account" class="form-control" v-model="setting.xero_sales_account_id">
                        <option v-for="(account) in xero_accounts" :value="account.id">{{account.name}}</option>
                    </select>
                </div>
                <div class="form-group d-none">
                    <label for="refund_account"> Select Account Type for Refund Data Import <span class="text-danger">*</span></label>
                    <select id="refund_account" class="form-control" v-model="setting.refund_account_type">
                        <option v-for="(account, index) in xero_account_types" :value="index">{{account}}</option>
                    </select>
                </div>
                <div class="form-group d-none">
                    <label for="refund_xero_account"> Select Xero Account For Refund <span class="text-danger">*</span></label>
                    <select id="refund_xero_account" class="form-control" v-model="setting.xero_refund_account_id">
                        <option v-for="(account) in xero_accounts" :value="account.id">{{account.name}}</option>
                    </select>
                </div>
                <div class="form-group d-none">
                    <label for="fees_account"> Select Account Type for Fee Data Import <span class="text-danger">*</span></label>
                    <select id="fees_account" class="form-control" v-model="setting.fee_account_type">
                        <option v-for="(account, index) in xero_account_types" :value="index">{{account}}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="fee_xero_account"> Select Xero Account For Fee <span class="text-danger">*</span></label>
                    <select id="fee_xero_account" class="form-control" v-model="setting.xero_fee_account_id">
                        <option v-for="(account) in xero_accounts" :value="account.id">{{account.name}}</option>
                    </select>
                </div>
                <div class="form-group d-none">
                    <label for="invoice_grouping">Create sales invoice per<span class="text-danger">*</span></label>
                    <select id="invoice_grouping" class="form-control" v-model="setting.invoice_grouping">
                        <option v-for="(group, index) in invoice_grouping_variants" :value="index">{{group}}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="disable_sales_feed">Allow HitPay to Automatically Create Invoices for HitPay Sales and Fees<span class="text-danger">*</span></label>
                    <select id="disable_sales_feed" class="form-control" v-model="setting.disable_sales_feed">
                        <option v-for="(group, index) in enable_sales_feed_variants" :value="index">{{group}}</option>
                    </select>
                </div>


                <button id="createBtn" class="btn btn-success btn-lg btn-block mb-3 shadow-sm" @click="saveSettings()" :disabled="is_updating">
                    {{isUpdate?'Update': 'Save'}}
                    <i class="fas fa-spin fa-spinner" :class="{
                'd-none' : !is_updating
            }"></i></button>
            </div>
        </div>
    </div>
</template>

<script>
    import Datepicker from 'vuejs-datepicker';
    export default {
        name: "XeroSettings",
        components: {
            Datepicker
        },
        data: () => {
            return {
                is_updating: false,
                setting:  {
                    sync_date: null,
                    paynow_btn_text: 'PayNow',
                    xero_branding_theme: null,
                    sales_account_type: 4,
                    refund_account_type: 4,
                    fee_account_type: 4,
                    invoice_grouping: 'INDIVIDUAL',
                    xero_sales_account_id: null,
                    xero_refund_account_id: null,
                    xero_fee_account_id: null,
                    xero_payout_account_id: null,
                    disable_sales_feed: 0,
                },
                errors: {},
                email: null,
                organization: null,
                xero_account_types: [],
                xero_branding_themes: [],
                xero_accounts: [],
                bank_accounts: [],
                invoice_grouping_variants: [],
                enable_sales_feed_variants: ['Yes', 'No'],
                isUpdate: false,
            }
        },
        mounted() {
            if(window.showDisconnetPopup) {
                $('#disconnectedModal').modal('toggle');
            }
            if (window.xeroAccountTypes)
            {
                this.xero_account_types = window.xeroAccountTypes
            }
            if (window.xeroBrandingThemes)
            {
                this.xero_branding_themes = window.xeroBrandingThemes;
            }
            if (window.xeroInvoiceGrouping)
            {
                this.invoice_grouping_variants = window.xeroInvoiceGrouping

            }
            if(window.xeroAccounts) {
                this.xero_accounts = window.xeroAccounts;
            }
            if(window.bankAccounts) {
                this.bank_accounts = window.bankAccounts;
            }
            this.email = window.Business.xero_email;
            this.organization = window.Business.xero_organization_name;

            if (window.Business.xero_sync_date)
            {

                this.setting = {
                    sync_date: window.Business.xero_sync_date,
                    paynow_btn_text: window.Business.paynow_btn_text,
                    xero_branding_theme: window.Business.xero_branding_theme,
                    sales_account_type: this.xero_account_types.indexOf(window.Business.xero_sales_account_type),
                    refund_account_type: this.xero_account_types.indexOf(window.Business.xero_refund_account_type) ,
                    fee_account_type: this.xero_account_types.indexOf(window.Business.xero_fee_account_type) ,
                    invoice_grouping: window.Business.xero_invoice_grouping,
                    disable_sales_feed: window.Business.xero_disable_sales_feed,
                    xero_payout_account_id: window.Business.xero_payout_account_id,
                    xero_sales_account_id: window.Business.xero_sales_account_id,
                    xero_refund_account_id: window.Business.xero_refund_account_id,
                    xero_fee_account_id: window.Business.xero_fee_account_id
                }
                this.isUpdate = true;

                if(!window.Business.hasXeroPaymentGateway) {
                    $('#exportModal').modal('toggle');
                }
            }
        },
        methods: {
            gatewaySettingsRedirect()
            {
                location.href = location.href.replace('integration/xero/home', 'gateway-provider');
            },
            saveSettings()
            {
                this.errors = {};
                if (this.setting.sync_date === null)
                {
                    this.errors.sync_date = 'Sync date  is required';
                }
                if (this.setting.paynow_btn_text === null)
                {
                    this.errors.paynow_btn_text = 'Xero PayNow button text is required';
                }
                if (this.setting.xero_payout_account_id === null)
                {
                    this.errors.xero_payout_account_id_text = 'Xero Payout account is required';
                }
                if (this.setting.refund_account_type === null)
                {
                    this.errors.refund_account_type = 'Account type is required';
                }
                if (this.setting.xero_branding_theme === null)
                {
                    this.errors.xero_branding_theme = 'Xero branding theme is required';
                }
                if (this.setting.sales_account_type === null)
                {
                    this.errors.sales_account_type = 'Account type is required';
                }
                if (this.setting.fee_account_type === null)
                {
                    this.errors.fee_account_type = 'Account type is required';
                }
                if (Object.keys(this.errors).length > 0) {
                   this.showError(_.first(Object.keys(this.errors)));
                } else {
                    this.is_updating = true;
                    this.setting.sales_account_type = this.xero_account_types[this.setting.sales_account_type];
                    this.setting.refund_account_type = this.xero_account_types[this.setting.refund_account_type];
                    this.setting.fee_account_type = this.xero_account_types[this.setting.fee_account_type];
                    this.setting.invoice_grouping = this.setting.invoice_grouping;
                    axios.post(this.getDomain('business/' + Business.id + '/integration/xero/save-settings', 'dashboard'), this.setting).then(({data}) => {
                      //
                        this.is_updating = false;
                        window.location.href = data.redirect_url;
                    }).catch(({response}) => {
                        if (response.status === 422) {
                            _.forEach(response.data.errors, (value, key) => {
                                this.errors[key] = _.first(value);
                            });
                            this.showError(_.first(Object.keys(this.errors)));
                        }
                        this.is_updating = false; // In case of the status code is other
                    });
                }

            },
            disconnectXeroAccount()
            {
                axios.get(this.getDomain('business/' + Business.id + '/integration/xero/disconnect', 'dashboard')).then(({data}) => {
                    //
                    this.is_updating = false;
                    window.location.href = data.redirect_url;
                }).catch(({response}) => {
                    if (response.status === 422) {
                        _.forEach(response.data.errors, (value, key) => {
                            this.errors[key] = _.first(value);
                        });
                        this.showError(_.first(Object.keys(this.errors)));
                    }
                    this.is_updating = false; // In case of the status code is other
                });
            },
            showError(firstErrorKey) {
                if (firstErrorKey !== undefined) {
                    this.scrollTo('#' + firstErrorKey);

                    $('#' + firstErrorKey).focus();
                }
                this.is_updating = false;
            },
        }
    }
</script>

<style scoped>

</style>
