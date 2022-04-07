<template>
    <div>
        <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#exportPayoutBreakdown">Export Payout Breakdown</button>
        <div class="modal fade" id="exportPayoutBreakdown" tabindex="-1" role="dialog" aria-labelledby="exportPayoutBreakdownLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exportPayoutBreakdownLabel">Export Payout Breakdown</h5>
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
                                <select v-if="withTimeOptions" v-model="date.from.time" aria-label="Time" class="custom-select" :disabled="is_requesting">
                                    <option v-for="time in form.from_times" :value="time">{{ time }}</option>
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
                                <select v-if="withTimeOptions" v-model="date.to.time" aria-label="Time" class="custom-select" :disabled="is_requesting">
                                    <option v-for="time in form.to_times" :value="time">{{ time }}</option>
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
        props: {
            withTimeOptions : {
                type: Boolean,
                default: false,
            }
        },

        data() {
            let from_times = []; // time array
            let to_times = []; // time array
            let tt = 0; // start time

            //loop to increment the time and push results in array
            for (let i = 0; tt < 24 * 60; i++) {
                // getting hours of day in 0-24 format
                let hh = Math.floor(tt / 60);
                // getting minutes of the hour in 0-55 format
                let mm = ( tt % 60 );
                // pushing data in array in [00:00 - 12:00 AM/PM format]
                from_times[i] = ( "0" + ( hh ) ).slice(-2) + ":" + ( "0" + mm ).slice(-2) + ":00";

                mm = (mm === 0 ? 29 : 59);

                to_times[i] = ( "0" + ( hh ) ).slice(-2) + ":" + ( "0" + mm ).slice(-2) + ":59";

                tt = tt + 30;
            }

            return {
                error: null,
                modal: null,
                date: {
                    from: {
                        year: '',
                        month: '',
                        day: '',
                        time: '00:00:00',
                    },
                    to: {
                        year: '',
                        month: '',
                        day: '',
                        time: '00:00:00',
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
                    from_times: from_times,
                    to_times: to_times,
                },
                is_requesting: false,
                is_succeeded: false,
            }
        },

        mounted() {
            this.modal = $('#exportPayoutBreakdown');
            this.modal.on('hidden.bs.modal', () => {
                this.is_requesting = false;
                this.is_succeeded = false;
            });

            let date = new Date();

            this.date.from.year = date.getFullYear();
            this.date.from.month = date.getMonth() + 1;
            this.date.from.day = date.getDate();
            this.date.from.time = "00:00:00";

            this.date.to.year = date.getFullYear();
            this.date.to.month = date.getMonth() + 1;
            this.date.to.day = date.getDate();
            this.date.to.time = "23:59:59";
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

                axios.post(this.getDomain('business/' + Business.id + '/payment-provider/paynow/payout-breakdown/export', 'dashboard'), {
                    starts_at: `${this.date.from.year}-${fromMonth}-${fromDay} ${this.date.from.time}`,
                    ends_at: `${this.date.to.year}-${toMonth}-${toDay} ${this.date.to.time}`,
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
