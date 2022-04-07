<template>
    <div class="p-4">
        <button class="btn btn-primary" data-toggle="modal" data-target="#exportDeliveryModal">Delivery Report</button>
        <div class="modal fade" id="exportDeliveryModal" tabindex="-1" role="dialog" aria-labelledby="exportModalLabel"
             aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exportModalLabel">Delivery Report</h5>
                        <button id="closeBtn" type="button" class="close" data-dismiss="modal" aria-label="Close"
                                :disabled="is_requesting">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div v-if="is_succeeded" class="modal-body">
                        File will be sent to your email shortly.
                    </div>
                    <div v-else class="modal-body">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="pickup" name="pickup"
                                   v-model="pickup">
                            <label class="form-check-label" for="pickup">
                                Pick up Orders
                            </label>
                        </div>
                        <h5 v-if="!pickup" class="mt-4">Delivery date</h5>
                        <h5 v-else class="mt-4">Self collection date</h5>
                        <div class="form-group">
                            <label class="col-form-label">From:</label>
                            <div class="input-group">
                                <select v-model="deliveryDate.from.year" aria-label="Year" class="custom-select"
                                        :disabled="is_requesting">
                                    <option v-for="year in form.years" :value="year">{{ year }}</option>
                                </select>
                                <select v-model="deliveryDate.from.month" aria-label="Year" class="custom-select"
                                        :disabled="is_requesting">
                                    <option v-for="(month, index) in form.months" :value="index">{{
                                            month
                                        }}
                                    </option>
                                </select>
                                <select v-model="deliveryDate.from.day" aria-label="Year" class="custom-select"
                                        :disabled="is_requesting">
                                    <option v-for="day in form.days" :value="day">{{ day }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-form-label">To:</label>
                            <div class="input-group">
                                <select v-model="deliveryDate.to.year" aria-label="Year" class="custom-select"
                                        :disabled="is_requesting">
                                    <option v-for="year in form.years" :value="year">{{ year }}</option>
                                </select>
                                <select v-model="deliveryDate.to.month" aria-label="Year" class="custom-select"
                                        :disabled="is_requesting">
                                    <option v-for="(month, index) in form.months" :value="index">{{
                                            month
                                        }}
                                    </option>
                                </select>
                                <select v-model="deliveryDate.to.day" aria-label="Year" class="custom-select"
                                        :disabled="is_requesting">
                                    <option v-for="day in form.days" :value="day">{{ day }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" id="csv" name="docType"
                                   v-model="docType" value="csv">
                            <label class="form-check-label" for="csv">
                                CSV
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="docType" id="pdf"
                                   v-model="docType" value="pdf">
                            <label class="form-check-label" for="pdf">
                                PDF
                            </label>
                        </div>
                        <p v-if="error" class="text-danger">{{ error }}</p>
                        <div class="text-right">
                            <button id="downloadBtn" type="button" class="btn btn-primary"
                                    @click.prevent="requestReport" :disabled="is_requesting">
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
    data() {
        return {
            error: null,
            modal: null,
            deliveryDate: {
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
            pickup: false,
            docType: 'csv',
            form: {
                years: [
                    '2018', '2019', '2020', '2021','2022'
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
        this.modal = $('#exportDeliveryModal');
        this.modal.on('hidden.bs.modal', () => {
            this.is_requesting = false;
            this.is_succeeded = false;
        });

        let date = new Date();

        this.deliveryDate.from.year = date.getFullYear();
        this.deliveryDate.from.month = date.getMonth() + 1;
        this.deliveryDate.from.day = date.getDate();

        this.deliveryDate.to.year = date.getFullYear();
        this.deliveryDate.to.month = date.getMonth() + 1;
        this.deliveryDate.to.day = date.getDate();

    },


    methods: {
        requestReport() {
            this.is_requesting = true;

            let fromDelMonth = this.deliveryDate.from.month;

            if (fromDelMonth < 10) {
                fromDelMonth = '0' + fromDelMonth;
            }

            let fromDelDay = this.deliveryDate.from.day;

            if (fromDelDay < 10) {
                fromDelDay = '0' + fromDelDay;
            }

            let toDelMonth = this.deliveryDate.to.month;

            if (toDelMonth < 10) {
                toDelMonth = '0' + toDelMonth;
            }

            let toDelDay = this.deliveryDate.to.day;

            if (toDelDay < 10) {
                toDelDay = '0' + toDelDay;
            }

            let submissionData = {
                delivery_start: this.deliveryDate.from.year + '-' + fromDelMonth + '-' + fromDelDay,
                delivery_end: this.deliveryDate.to.year + '-' + toDelMonth + '-' + toDelDay,
                docType: this.docType,
                pickup: this.pickup
            }

            axios.post(this.getDomain('business/' + Business.id + '/order/delivery-report', 'dashboard'), submissionData).then(({data}) => {
                this.is_requesting = false;
                this.is_succeeded = true;
            }).catch(({response}) => {
                if (response.status === 422) {
                    this.is_requesting = false;
                    this.error = response.data.message;
                }
            });
        },
    },
}
</script>
