<template>
    <div>
        <button v-if="exportEnabled()" class="btn btn-primary" data-toggle="modal" data-target="#exportModal">Export Products</button>
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
                            <div class="form-check">
                                <input class="form-check-input" v-model="export_option" type="radio" name="export_option" id="all_products" value="all">
                                <label class="form-check-label" for="all_products">
                                    All products
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" v-model="export_option" type="radio" name="export_option" id="inventory" value="inventory">
                                <label class="form-check-label" for="inventory" >
                                    Products with inventory lower than
                                </label>
                            </div>
                            <input type="number" v-model="inventory" class="form-control mb-2 mt-2" :class="{'is-invalid': error && error.includes('Inventory') && export_option==='inventory'}" 
                            :disabled="is_requesting" v-if="export_option==='inventory'" @keypress="isNumber($event)">
                            <div class="form-check">
                                <input class="form-check-input" v-model="export_option" type="radio" name="export_option" id="created_before" value="created_before">
                                <label class="form-check-label" for="created_before" >
                                    Products created before
                                </label>
                            </div>
                            <div class="input-group mt-2" v-if="export_option==='created_before'">
                                <select v-model="date.before.year" aria-label="Year" class="custom-select" :disabled="is_requesting">
                                    <option v-for="year in form.years" :value="year">{{ year }}</option>
                                </select>
                                <select v-model="date.before.month" aria-label="Year" class="custom-select" :disabled="is_requesting">
                                    <option v-for="(month, index) in form.months" :value="index">{{ month }}</option>
                                </select>
                                <select v-model="date.before.day" aria-label="Year" class="custom-select" :disabled="is_requesting">
                                    <option v-for="day in form.days" :value="day">{{ day }}</option>
                                </select>
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
    import isNumber from '../../mixins/NumberValidationMixin';
    export default {
        props: {
            current_business_user: Object
        },
        mixins: [isNumber],
        data() {
            return {
                error: null,
                modal: null,
                export_option: null,
                inventory: null,
                date: {
                    before: {
                        year: '',
                        month: '',
                        day: '',
                    },
                },
                form: {
                    years: [
                        '2018', '2019', '2020', '2021', '2022'
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

            this.date.before.year = date.getFullYear();
            this.date.before.month = date.getMonth() + 1;
            this.date.before.day = date.getDate();
        },

        methods: {
            exportEnabled() {
                return this.current_business_user.permissions.canExportProducts;
            },
            requestReport() {
                if (!this.export_option) {
                    this.error = 'Oops, Please select option above to export.';
                    return false;
                }

                if (this.export_option === 'inventory' && !this.inventory) {
                    this.error = 'Oops, Inventory lower than field is required.';
                    return false;
                }

                this.is_requesting = true;

                let beforeMonth = this.date.before.month;

                if (beforeMonth < 10) {
                    beforeMonth = '0' + beforeMonth;
                }

                let beforeDay = this.date.before.day;

                if (beforeDay < 10) {
                    beforeDay = '0' + beforeDay;
                }

                const before_date = this.date.before.year + '-' + beforeMonth + '-' + beforeDay;

                axios.post(this.getDomain('business/' + Business.id + '/product/export', 'dashboard'), {
                    export_option: this.export_option,
                    before_date: before_date,
                    inventory: this.inventory
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
