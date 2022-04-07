<template>
    <div>
        <button class="btn btn-primary" data-toggle="modal" data-target="#exportModal">Export Partners</button>
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
                        <p v-if="error" class="text-danger">{{ error }}</p>
                        <div class="text-right">
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
        data() {
            return {
                error: null,
                modal: null,
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
                form: {
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

                let url = this.getDomain('partners/export', 'admin')

                axios.post(url, {
                    starts_at: this.date.from.year + '-' + fromMonth + '-' + fromDay,
                    ends_at: this.date.to.year + '-' + toMonth + '-' + toDay,
                }).then(({data}) => {
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
