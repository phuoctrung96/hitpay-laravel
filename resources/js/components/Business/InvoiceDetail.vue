<style lang="scss">
/* Customize the radio checkbox */
.label-checkbox {
    position: relative;
    padding-left: 32px;
    margin-bottom: 22px;
    cursor: pointer;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}

.label-checkbox input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
}

.checkmark {
    position: absolute;
    top: 50%;
    left: 0;
    height: 24px;
    width: 24px;
    border: 1px solid #D4D6DD;
    border-radius: 50%;
    margin-top: -12px;
}

.label-checkbox .checkmark:after {
    top: 5px;
    left: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.label-checkbox input:checked ~ .checkmark {
    background-color: #FFF;
}

.label-checkbox .checkmark:after {
    background: #011B5F;
}

.checkmark:after {
    content: "";
    position: absolute;
    display: none;
}

.label-checkbox input:checked ~ .checkmark:after {
    display: block;
}

</style>
<template>
    <div class="invoice-section">
        <div v-if="message"  class="alert border-top border-left-0 border-right-0 border-bottom-0 rounded-0 mb-0 alert-success">
            {{ message }}
        </div>
        <div class="row copied-message">
            <div class="col-lg-8">
                <div class="alert alert-success" role="alert">
                    The payment link has been copied!
                </div>
            </div>
        </div>
        <div class="card card-payment-detail border-0 shadow-sm">
            <div class="payment-detail" v-if="invoice.status != 'sent'">
                <div class="card-body border-top">
                    <div class="row">
                        <div class="top-meta d-lg-flex justify-content-between align-items-center">
                            <div class="title">
                                <p>Payment Details</p>
                            </div>
                            <div class="payment-status d-flex justify-content-between align-items-center">
                                <div class="paid-amount" v-if="invoice.status == 'partiality_paid'">
                                    <p>Paid: <span>{{invoice.payment_detail_paid}}</span></p>
                                </div>
                                <div class="pending-amount" v-if="invoice.status == 'partiality_paid'">
                                    <p>Pending: <span>{{invoice.payment_detail_pending}}</span></p>
                                </div>
                                <div class="status">
                                    <p>
                                        <span class="is-status paid" v-if="invoice.status == 'paid'">Paid</span>
                                        <span class="is-status text-nowrap partiality" v-if="invoice.status == 'partiality_paid'">Partially paid</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tbl-payment-detail">
                        <div class="top-title d-none d-lg-flex">
                            <p class="d-lg-block title iw-25 first">Amount</p>
                            <p class="d-lg-block title iw-25">Date</p>
                            <p class="d-lg-block title iw-25">Payment method</p>
                            <p class="d-lg-block title iw-25 last">Payment Charge ID</p>
                        </div>
                        <!--Status = Paid-->
                        <div class="row-product d-lg-flex" v-if="invoice.status == 'paid' && !invoice.allow_partial_payments && invoice.charges.length > 0 ">
                            <p class="d-flex justify-content-between align-items-center iw-25 first">
                                <span class="title d-lg-none">Amount</span> {{business.currency.toUpperCase()}} {{ parseFloat(invoice.payment_request.amount).toFixed(2) }}</p>
                            <p class="d-flex justify-content-between align-items-center iw-25">
                                <span class="title d-lg-none">Date</span> {{ invoice.charges[0] ? getTime(invoice.charges[0].created_at) : ''}}</p>
                            <p class="d-flex justify-content-between align-items-center iw-25">
                                <span class="title d-lg-none">Payment method</span> <img :src="getLogoPayment(invoice.charges[0].payment_provider_charge_method)" class="icon-payment-method align-self-center mr-3" alt="PayNow"> </p>
                            <p class="d-flex justify-content-between align-items-center iw-25 last">
                                <span class="title d-lg-none">Payment Charge ID</span> {{ invoice.charges[0].id }} </p>
                        </div>
                        <!--Status = Partiality-->
                        <template v-for="(item, key) in partial_payments">
                            <div v-if="item.payment_request.getPayments" class="row-product d-lg-flex" :key="key">
                                <p class="d-flex justify-content-between align-items-center iw-25 first">
                                    <span class="title d-lg-none">Amount</span> {{business.currency.toUpperCase()}} {{ parseFloat(item.amount).toFixed(2) }}</p>
                                <p class="d-flex justify-content-between align-items-center iw-25">
                                    <span class="title d-lg-none">Date</span> {{ item.payment_request.getPayments ? getTime(item.payment_request.getPayments.created_at) : '' }}</p>
                                <p class="d-flex justify-content-between align-items-center iw-25">
                                    <span class="title d-lg-none">Payment method</span> <img :src="getLogoPayment(item.payment_request.getPayments?  item.payment_request.getPayments.payment_provider_charge_method : '')" class="icon-payment-method align-self-center mr-3" alt="PayNow"></p>
                                <p class="d-flex justify-content-between align-items-center iw-25 last">
                                    <span class="title d-lg-none">Payment Charge ID</span> {{item.payment_request.getPayments? item.payment_request.getPayments.id : ''}}</p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
        <div class="card card-invoice-detail border-0 shadow-sm">
            <div class="invoice-detail">
                <div class="card-body border-top">
                    <div class="top-meta d-flex justify-content-between align-items-center">
                        <div class="title">
                            <p>Payment Details</p>
                        </div>
                        <div class="copy" @click="copyInvoiceLink()" v-if="invoice.status != 'paid'">
                            <span>
                                <img src="/images/ico-copy.svg"> Copy invoice link
                            </span>
                        </div>
                    </div>
                    <div class="payment-status d-flex justify-content-between align-items-center">
                        <div class="logo">
                            <img :src="business_logo" alt="logo" />
                        </div>
                        <div class="status" v-if="invoice.status == 'sent'">
                            <p> <span class="is-status sent">Sent</span></p>
                        </div>
                    </div>
                    <div class="d-flex d-2col justify-content-between">
                        <div class="form-group w-50 mr-4">
                            <label for="invoice_date" class="d-block">Invoice date</label>
                            <input id="invoice_date" class="form-control" title="" v-model="invoice.invoice_date" readonly>
                        </div>
                        <div class="form-group w-50">
                            <label for="due_date" class="d-block">Due date</label>
                            <input id="due_date" class="form-control" title="" v-model="invoice.due_date" readonly>
                        </div>
                    </div>
                    <div>
                        <label>Select customer</label>
                        <input class="form-control" title="" v-model="customer.email" readonly>
                    </div>
                    <div class="d-flex d-2col justify-content-between mt-3">
                        <div class="form-group w-50 mr-4">
                            <label>Invoice number </label>
                            <input id="due_date" class="form-control" title="" :value="invoice.invoice_number" readonly>
                        </div>
                        <div class="form-group w-50">
                            <label for="currency_list">Select Currency </label>
                            <input id="due_date" class="form-control text-uppercase" title="" v-model="invoice.currency" readonly>
                        </div>
                    </div>
                    <div class="add-product-section bg-light">
                        <div class="top-section">
                            <template v-if="payment_by_products">
                                <div class="table-items">
                                    <div class="lg-title">
                                        <div class="field search">Item</div>
                                        <div class="field qty">Qty</div>
                                        <div class="field price">Price</div>
                                        <div class="field discount">Discount</div>
                                        <div class="field total">Total</div>
                                        <!-- <div class="field delete"></div> -->
                                    </div>
                                    <template v-for="(item, key) in added_products">
                                        <div class="item-add-product d-lg-flex align-items-center">
                                            <div class="field search">
                                                <label class="title">Item</label>
                                                <input type="text" class="form-control" readonly :value="added_products[key].product ? added_products[key].product.name : ''">
                                            </div>
                                            <div class="field qty">
                                                <label class="title">Qty</label>
                                                <input v-model.number="added_products[key].quantity" title="Quantity" class="form-control" readonly>
                                            </div>
                                            <div class="field price">
                                                <label class="title">Price</label>
                                                <input type="text" class="form-control" readonly :value="added_products[key].variation ? added_products[key].variation.price.toFixed(2) : ''">
                                            </div>
                                            <div class="field discount">
                                                <label class="title">Discount</label>
                                                <input v-model.number="added_products[key].discount" title="Discount" class="form-control" readonly>
                                            </div>
                                            <div class="field total align-items-center justify-content-between">
                                                <label class="title mb-0">Total</label>
                                                <template v-if="added_products[key].product">
                                                    {{ itemPriceWithDiscount(added_products[key]).toFixed(2) }}
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <template v-else>
                                <input v-model.number="invoice.amount_no_tax" class="form-control" placeholder="$250" step="0.01" title="Amount" readonly>
                            </template>
                        </div>
                        <div v-if="enable_tax_setting" class="tax-setting">
                            <div class="is-tax-setting d-flex justify-content-between align-items-center">
                                <label>{{invoice.tax_setting_title}} - {{invoice.tax_setting_rate}}%</label>
                                <p><span>Tax amount:</span> {{ taxAmount() }}</p>
                            </div>
                        </div>
                        <div class="total border-top">
                            <div class="is-total d-flex justify-content-between">
                                <span>Total</span>
                                <span class="amount">{{invoice.currency.toUpperCase()}} {{ invoiceAmount() }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-if="invoice.allow_partial_payments" class="mt-3" >
                        <label for="allow_partial_payments" class="">Partial payments are allowed</label>
                      <div v-for="(payment, index) in partial_payments" class="item mt-1">
                        Payment {{ (index + 1) }}
                        <div class="d-flex justify-content-between payment-method mt-1">
                          <div class="form-group w-50 mr-4">
                            <input type="text" class="form-control" v-model="partial_payments[index].amount"
                                   placeholder="Amount" disabled>
                          </div>
                          <div class="form-group w-50">
                            <datepicker id="due_date" disabled v-model="partial_payments[index].due_date"
                                        :bootstrap-styling="true"
                                        input-class="bg-white" placeholder="Due date" :format="'dd/MM/yyyy'"
                                        class="w-100"
                            ></datepicker>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div v-else class="mt-3">
                        <label for="allow_partial_payments" class="">Partial payments are not allowed</label>
                    </div>
                    <div class="form-group mt-3">
                        <label for="reference">Reference</label>
                        <input id="reference" class="form-control" readonly title="" v-model="invoice.reference" :class="{
                            'is-invalid' : errors.reference
                        }" placeholder="November supply invoice" :disabled="is_processing">
                        <span class="invalid-feedback" role="alert">{{ errors.reference }}</span>
                    </div>
                    <div class="form-group mt-3">
                        <label for="description">Description</label>
                        <textarea id="description" v-model="invoice.memo" readonly class="form-control description" rows="3" :class="{
                            'is-invalid' : errors.memo
                        }" placeholder="Details of this invoice…" :disabled="is_processing"></textarea>
                        <span class="invalid-feedback" role="alert">{{ errors.memo }}</span>
                    </div>
                    <div class="mt-3 upload">
                        <label for="description">Attached files</label>
                        <template v-if="invoice.attached_file">
                            <span class="d-block">{{ invoice.attached_file }}</span>
                        </template>
                    </div>
                    <div class="d-flex btn-create-invoice justify-content-between">
                        <button class="btn p-2 px-3" :disabled="is_processing" :style="{border : '1px solid '+mainColor, color: mainColor, backgroundColor: 'white', fontSize: '14px'}" @click.prevent="editInvoice()" v-if="invoice.status != 'paid' && invoice.status != 'partiality_paid'">
                            Edit
                        </button>
                        <button class="btn p-2 px-3" :style="{backgroundColor: mainColor, color: 'white', fontSize: '14px'}" v-if="invoice.status != 'paid'" @click.prevent="resendInvoice()" :disabled="is_processing"  >
                            Resend invoice
                        </button>
                        <button class="btn p-2 px-3" :style="{backgroundColor: mainColor, color: 'white', fontSize: '14px'}" v-if="invoice.status == 'partiality_paid'" @click.prevent="senReminder()" :disabled="is_processing" >
                            Send reminder
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>

<script>
import Datepicker from "vuejs-datepicker";

export default {
    components: {
        Datepicker,
    },
    props: {
        currency_list: {
            type: Object,
            required: true
        },
        zero_decimal_list: {
            type: Array,
        },
        business_logo: {
            type: String
        }
    },
    data() {
        return {
            business: [],
            customer: {
                id: null,
                name: '',
                email: '',
                phone_number: '',
                street: '',
                state: '',
                city: '',
                postal_code: '',
                country: '',
                remark: '',
            },
            customers_result: [],
            cycle_list: [],
            errors: {},
            is_processing: false,
            is_searching_product: false,
            tax_setting: [],
            invoice: {
                id: null,
                customer_id: null,
                reference: '',
                invoice_number: '',
                amount: '',
                amount_no_tax: '',
                email: '',
                due_date: '',
                invoice_date: '',
                currency: 'sgd',
                auto_invoice_number: false,
                products: [],
                tax_setting: '',
                tax_setting_title: '',
                tax_setting_rate: '',
                memo: '',
                status: '',
                partial_payments: null,
                allow_partial_payments: false,
                attached_file: null,
                payment_request: null,
                payment_detail_paid: 0,
                payment_detail_pending: 0,
                charges: []
            },
            search_product: {
                keywords: '',
                search_results: [],
                timeout: null,

            },
            added_products: [{}],
            partial_payments: [],
            tax_list: [],
            search_customer: '',
            timeout: null,
            is_customer: true,
            create_customer_url: null,
            currency: 'sgd',
            currency_display: 'sgd',
            enable_tax_setting: false,
            total_amount: 0,
            attached_file: null,
            draft_mode: false,
            payment_by_products: true,
            mainColor: '#011B5F',
            innerDropDown: {
                'position': 'absolute',
                'z-index': '100',
                'width': '100%'
            },
            outerDropDown: {
                'position': 'relative',
                'width': '100%'
            },
            message: null,
        }
    },

    mounted() {
        if (window.Business)
            this.business = window.Business;
        if (window.Invoice) {
            this.invoice = {
                id: window.Invoice.id,
                customer_id: window.Invoice.customer_id,
                invoice_number: window.Invoice.invoice_number,
                amount: window.Invoice.amount,
                amount_no_tax: window.Invoice.amount_no_tax,
                due_date: this.getTime(window.Invoice.due_date ? window.Invoice.due_date : ""),
                invoice_date: window.Invoice.invoice_date ? this.getTime(window.Invoice.invoice_date ? window.Invoice.invoice_date : "") : "",
                currency: window.Invoice.currency ?? "",
                reference: window.Invoice.reference ?? "",
                auto_invoice_number: false,
                products: window.Invoice.products,
                tax_setting: window.Invoice.tax_settings_id ?? "",
                memo: window.Invoice.memo ?? "",
                status: window.Invoice.status,
                attached_file: window.Invoice.attached_file ?? "",
                payment_request: window.Invoice.payment_request,
                payment_detail_paid: 0,
                charges: window.Invoice.charges
            };
            this.total_amount = parseFloat(this.invoice.amount);

            if (this.invoice.tax_setting) {
                this.enable_tax_setting = true
            }
            this.tax_setting = window.Tax_Settings;

            this.addCustomer(window.Customer);

            this.payment_by_products = this.invoice.products.length > 0 ? true : false;
        }

        if (window.Invoice && window.Invoice.products) {
            this.added_products = window.Invoice.products;
            this.added_products.forEach(function(prod, index, arr) {
                arr[index].variation.price = arr[index].variation.price;
            })
        }

        if (window.partialPayments) {
            this.partial_payments = window.partialPayments;
            let paid_pending = 0;
            this.partial_payments.forEach(function(part, index, arr) {
                arr[index].due_date = arr[index].due_date ? new Date((arr[index].due_date).replace(/-/g, "/")) : "";
                if(arr[index].payment_request.status == "pending" ){
                     paid_pending += parseFloat(arr[index].payment_request.amount)
                }
            });
            this.invoice.allow_partial_payments = true;
            this.invoice.payment_detail_pending = paid_pending;
            this.invoice.payment_detail_paid =  parseFloat(this.invoice.amount) -  parseFloat(this.invoice.payment_detail_pending);
        }

        this.tax_list = window.Tax_Settings;
        this.currency = Business.currency;
        this.currency_display = this.currency.toUpperCase();
        this.create_customer_url = this.getDomain('business/' + Business.id + '/customer/create', 'dashboard');
        if(this.invoice.tax_setting != "") {
           let taxt_setting = this.tax_setting.find(x=>x.id === this.invoice.tax_setting);
           if(taxt_setting) {
               this.invoice.tax_setting_title = taxt_setting.name;
               this.invoice.tax_setting_rate = taxt_setting.rate;
            }
        }
    },

    methods: {
        resendInvoice() {
            this.is_processing = true;
            axios.post(this.getDomain('business/' + Business.id + '/invoice/'+ this.invoice.id +'/resend', 'dashboard')).then(({data}) => {
                this.message = "The invoice has been successful resend!"
                this.is_processing = false;
            }).catch(({response}) => {
                this.is_processing = false;
            });
        },
        editInvoice() {
            window.location.href = this.getDomain('business/' + Business.id + '/invoice/'+ this.invoice.id +'/edit', 'dashboard');
        },
        senReminder() {
            this.is_processing = true;
            axios.post(this.getDomain('business/' + Business.id + '/invoice/'+ this.invoice.id +'/remind', 'dashboard')).then(({data}) => {
                this.message = "The invoice has been successful reminded!"
                this.is_processing = false;
            }).catch(({response}) => {
                this.is_processing = false;
            });
        },
        copyInvoiceLink() {
            let Url = this.getDomain(Business.id + '/'+ this.invoice.id, 'invoice');
            navigator.clipboard.writeText(Url);
            var copiedMessage = document.querySelector('.copied-message');
                if (copiedMessage) {
                    copiedMessage.style.display = 'block';
                    setTimeout(function () {
                        copiedMessage.style.display = 'none';
                    }, 2000)
                }
        },
        getTime(time) {
            if(time == "")
                return "";
            var t = time.split(/[- :]/);

            // Apply each element to the Date function
            var date = new Date(t[0], t[1]-1, t[2], t[3], t[4], t[5]);
            return ((date.getDate() > 9) ? date.getDate() : ('0' + date.getDate())) + '/' +((date.getMonth() > 8) ? (date.getMonth() + 1) : ('0' + (date.getMonth() + 1))) + '/' + date.getFullYear();
        },
        disallowDecimal(event) {
            if (event.keyCode === 190) {
                event.preventDefault();
            }
        },

        showError(firstErrorKey) {
            if (firstErrorKey !== undefined) {
                this.scrollTo('#' + firstErrorKey);

                $('#' + firstErrorKey).focus();
            }

            this.is_processing = false;
        },

        addCustomer(customer) {
            this.invoice.customer_id = customer.id;
            this.customer = customer;
            this.search_customer = '';
            this.customers_result = [];
        },

        removeCustomer(id) {
            this.invoice.customer_id = null;
            this.customer = {
                id: null,
                name: '',
                email: '',
                phone_number: '',
                street: '',
                state: '',
                city: '',
                postal_code: '',
                country: '',
                remark: '',
            };
            this.search_customer = '';
            this.customers_result = [];
        },

        searchProduct() {
            this.is_searching_product = true;

            clearTimeout(this.search_product.timeout);

            this.search_product.timeout = setTimeout(() => {
                if (this.search_product.keywords === '') {
                    this.search_product.search_results = [];
                } else {
                    axios.post(this.getDomain('business/' + Business.id + '/point-of-sale/', 'dashboard') + 'product', {
                        keywords: this.search_product.keywords,
                    }).then(({data}) => {
                        this.is_searching_product = false;
                        this.search_product.search_results = data;
                    });
                }
            }, 500);
        },

        addProduct(key, product, variation = null) {
            if (!this.added_products.find(x => (x.variation && x.variation.id === variation.id))) {
                this.added_products[key].product = product;
                this.added_products[key].variation = variation;
                this.added_products[key].quantity = 1;
                this.added_products[key].discount = 0;
                this.search_product.keywords = '';
                this.$forceUpdate();
            }
            this.invoice.currency = this.business.currency;
        },

        removeProduct(event, key) {
            event.preventDefault();

            this.added_products.splice(key, 1);
        },
        removePayment(event, key) {
            event.preventDefault();

            this.partial_payments.splice(key, 1);
        },
        isNumber(evt) {
            evt = (evt) ? evt : window.event;
            var charCode = (evt.which) ? evt.which : evt.keyCode;
            if ((charCode > 31 && (charCode < 48 || charCode > 57)) && charCode !== 46) {
                evt.preventDefault();
                ;
            } else {
                return true;
            }
        },
        itemPriceWithDiscount(item) {
            return (item.variation.price * item.quantity) - (item.discount ?? 0);
        },
        invoiceAmount() {
            let amount = this.total_amount != '' ? this.total_amount : 0;

            // if (this.added_products.length > 0 && this.payment_by_products) {
            //     amount = 0;
            //     this.added_products.forEach((item) => {
            //         amount += item.product ? this.itemPriceWithDiscount(item) : 0;
            //     })
            // }

            // this.invoice.amount_no_tax = amount.toFixed(2);

            // if (this.invoice.tax_setting && this.enable_tax_setting) {
            //     let tax = this.tax_list.find(x => x.id === this.invoice.tax_setting);

            //     amount += amount * tax.rate / 100;
            //     if (this.zero_decimal_list.includes(this.invoice.currency)) {
            //         amount = Math.ceil(amount);
            //     }
            // }
            // if (this.zero_decimal_list.includes(this.invoice.currency))
            //     return amount;

            return isNaN(parseFloat(amount)) ? '' : amount.toFixed(2);
        },
        taxAmount() {
            if (this.invoice.tax_setting && this.enable_tax_setting) {
                let amount = this.invoice.amount_no_tax;

                if (this.added_products.length > 0 && this.payment_by_products) {
                    amount = 0;
                    this.added_products.forEach((item) => {
                        amount += item.product ? this.itemPriceWithDiscount(item) : 0;
                    })
                }
                let tax = this.tax_list.find(x => x.id === this.invoice.tax_setting);

                return (amount * tax.rate / 100).toFixed(2);
            } else return 0;
        },
        getLogoPayment(payment_method) {
            if(payment_method == 'paynow_online'){
                return this.getDomain('icons/payment-methods-2/paynow.png');
            }
            if(payment_method == 'card_present'){
                return this.getDomain('icons/payment-methods-2/card_reader.svg');
            }
            if(payment_method == 'grabpay'){
                return this.getDomain('icons/payment-methods-2/grabpay.png');
            }
            if(payment_method == 'card'){
                return this.getDomain('icons/payment-methods-2/card.png');
            }
            if(payment_method == 'alipay'){
                return this.getDomain('icons/payment-methods-2/alipay.png');
            }
            if(payment_method == 'wechat'){
                return this.getDomain('icons/payment-methods-2/wechat.png');
            }
            if(payment_method == 'cash'){
                return this.getDomain('icons/payment-methods-2/cash.png');
            }

            return this.getDomain('icons/payment-methods/weblink.svg');
        }
    },
}
</script>
