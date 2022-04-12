<template>
    <div>
        <button v-if="exportEnabled()" class="btn btn-primary" data-toggle="modal" data-target="#exportModal">Export Charges</button>
        <div class="modal fade" id="exportModal" tabindex="-1" role="dialog" aria-labelledby="exportModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exportModalLabel">Export</h5>
                        <button id="closeBtn" type="button" class="close" data-dismiss="modal" aria-label="Close" :disabled="is_requesting">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div v-if="is_succeeded" class="modal-body">
                        The CSV will be sent to your email shortly.
                    </div>
                    <div v-else class="modal-body">
                        <div class="form-group">
                            <label class="col-form-label">Select Channel:</label>
                            <div class="input-group">
                                <select v-model="channel" aria-label="Channel" class="custom-select" :disabled="is_requesting">
                                    <option v-for="channel in form.channels" :value="channel.value">{{ channel.name }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group" v-if="channel === 'payment_gateway'">
                            <label class="col-form-label">Select Plugin:</label>
                            <div class="input-group">
                                <select v-model="plugin_provider" aria-label="Plugin" class="custom-select" :disabled="is_requesting">
                                    <option v-for="plugin in form.plugins" :value="plugin.value">{{ plugin.name }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-form-label">Select Payment Method:</label>
                            <div class="input-group">
                                <select v-model="payment_method" aria-label="Payment Method" class="custom-select" :disabled="is_requesting">
                                    <option v-for="method in form.methods" :value="method.value">{{ method.name }}</option>
                                    <option v-if="channel === 'point_of_sale'" value="cash">Cash</option>
                                    <option v-if="channel === 'point_of_sale'" value="paynow">PayNow</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-form-label">Select Date From:</label>
                            <div class="input-group">
                                <select v-model="date.from.year" aria-label="Year" class="custom-select" :disabled="is_requesting">
                                    <option v-for="year in form.years" :value="year">{{ year }}</option>
                                </select>
                                <select v-model="date.from.month" aria-label="Year" class="custom-select" :disabled="is_requesting">
                                    <option v-for="(month, index) in form.months" :value="index">{{ month }}</option>
                                </select>
                                <select v-model="date.from.day" aria-label="Year" class="custom-select" :disabled="is_requesting">
                                    <option v-for="day in form.days" :value="day">{{ day }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-form-label">Select Date To:</label>
                            <div class="input-group">
                                <select v-model="date.to.year" aria-label="Year" class="custom-select" :disabled="is_requesting">
                                    <option v-for="year in form.years" :value="year">{{ year }}</option>
                                </select>
                                <select v-model="date.to.month" aria-label="Year" class="custom-select" :disabled="is_requesting">
                                    <option v-for="(month, index) in form.months" :value="index">{{ month }}</option>
                                </select>
                                <select v-model="date.to.day" aria-label="Year" class="custom-select" :disabled="is_requesting">
                                    <option v-for="day in form.days" :value="day">{{ day }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label class="col-form-label">Fields Required:</label>
                            <div class="form-row">
                                <div v-for="(value, index) in fields" class="col-12 col-md-6">
                                    <div class="form-check mb-2">
                                        <input type="checkbox" class="form-check-input" v-model="fields[index]" :id="'fieldFor'+index" :disabled="is_requesting">
                                        <label class="form-check-label" :for="'fieldFor'+index">{{index }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="border-top pt-3">
                            <p v-if="error" class="text-danger">{{ error }}</p>
                            <button id="downloadBtn" type="button" class="btn btn-primary" @click.prevent="requestReport" :disabled="is_requesting">
                                Download <i v-if="is_requesting" class="fas fa-spinner fa-spin"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    export default {
        props: {
            current_business_user: Object
        },
        data() {
            return {
                error: null,
                modal: null,
                payment_method: null,
                plugin_provider: null,
                channel: null,
                date: {
                    from: {
                        year: '',
                        month: '',
                        day: '',
                    },
                    to: {
                        year: '',
                        month: '',
                        day: '',
                    },
                },
                fields: {
                    'Channel': true,
                    'Plugin': true,
                    'Plugin Reference': true,
                    'Method': true,
                    'Additional Reference': true,
                    'Order ID': true,
                    'Customer Name': true,
                    'Receipt Recipient': true,
                    'Remark': true,
                    'Product(s)': true,
                    'Payment Details': true,
                    'Store URL': true,
                    'Terminal ID': false
                },
                form: {
                    channels: [
                        {
                            "value": null,
                            "name": "All",
                        },
                        {
                            "value": "point_of_sale",
                            "name": "Point of Sale",
                        },
                        {
                            "value": "payment_gateway",
                            "name": "Payment Gateway",
                        },
                        {
                            "value": "store_checkout",
                            "name": "Store Checkout",
                        },
                    ],
                    methods: [
                        {
                            "value": null,
                            "name": "All",
                        },
                        {
                            "value": "paynow_online",
                            "name": "PayNow Online",
                        },
                        {
                            "value": "alipay",
                            "name": "Alipay",
                        },
                        {
                            "value": "wechat",
                            "name": "WeChat Pay",
                        },
                        {
                            "value": "card",
                            "name": "Card",
                        },
                        {
                            "value": "card_present",
                            "name": "Card Present (Terminal)",
                        },
                    ],
                    plugins: [
                        {
                            "value": null,
                            "name": "All",
                        },
                        {
                            "value": "woocommerce",
                            "name": "WooCommerce",
                        },
                        {
                            "value": "shopify",
                            "name": "Shopify",
                        },
                        {
                            "value": "payment-request",
                            "name": "Others",
                        },
                    ],
                    years: [
                        '2018', '2019', '2020','2021','2022'
                    ],
                    months: {
                        '1': 'January',
                        '2': 'February',
                        '3': 'March',
                        '4': 'April',
                        '5': 'May',
                        '6': 'June',
                        '7': 'July',
                        '8': 'August',
                        '9': 'September',
                        '10': 'October',
                        '11': 'November',
                        '12': 'December',
                    },
                    days: 31,
                },
                is_requesting: false,
                is_succeeded: false,
            }
        },

        mounted() {
            this.modal = $('#exportModal');
            this.modal.on('hidden.bs.modal', () => {
                this.is_requesting = false;
                this.is_succeeded = false;
            });

            let date = new Date();

            this.date.from.year = date.getFullYear();
            this.date.from.month = date.getMonth() + 1;
            this.date.from.day = date.getDate();

            this.date.to.year = date.getFullYear();
            this.date.to.month = date.getMonth() + 1;
            this.date.to.day = date.getDate();
        },


        methods: {
            exportEnabled() {
                return this.current_business_user.permissions.canExportCharges;
            },
            requestReport() {
                this.is_requesting = true;

                let fromMonth = this.date.from.month;

                if (fromMonth < 10) {
                    fromMonth = '0' + fromMonth;
                }

                let fromDay = this.date.from.day;

                if (fromDay < 10) {
                    fromDay = '0' + fromDay;
                }

                let toMonth = this.date.to.month;

                if (toMonth < 10) {
                    toMonth = '0' + toMonth;
                }

                let toDay = this.date.to.day;

                if (toDay < 10) {
                    toDay = '0' + toDay;
                }

                const start_date = this.date.from.year + '-' + fromMonth + '-' + fromDay;
                const end_date = this.date.to.year + '-' + toMonth + '-' + toDay;

                if (start_date > end_date) {
                    this.is_requesting = false;
                    this.error = 'Oops, date from should not be greater than date to.';
                } else {
                    axios.post(this.getDomain('business/' + Business.id + '/charge/export', 'dashboard'), {
                        starts_at: start_date,
                        ends_at: end_date,
                        payment_method: this.payment_method,
                        channel: this.channel,
                        plugin_provider: this.plugin_provider,
                        fields: this.fields,
                    }).then(({data}) => {
                        this.is_requesting = false;
                        this.is_succeeded = true;
                    }).catch(({response}) => {
                        if (response.status === 422) {
                            this.is_requesting = false;
                            this.error = response.data.message;
                        }
                    });
                }
            },
        },
    }
</script>
