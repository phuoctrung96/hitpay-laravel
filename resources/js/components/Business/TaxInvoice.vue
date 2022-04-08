<style scoped>

.card-invoice {
    padding: unset !important;
}

.table-invoice {
    margin-bottom: unset !important;
}

</style>
<template>
    <div class="card-body border-top card-invoice">
        <div v-if="is_succeded" class="alert alert-success border-left-0 border-right-0 rounded-0 alert-dismissible fade show"
             role="alert">
            Invoice will be sent to your email shortly
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true" @click="is_succeded=false;">&times;</span>
            </button>
        </div>
        <table class="table table-bordered table-invoice">
            <tbody>
            <tr v-for="(month,index) in months">
                <th scope="row">{{(index+1)}}</th>
                <td>Fee Invoice for {{ month.name }} {{month.year}}</td>
                <td><button class="btn btn-light btn-sm" @click="downloadInvoice(month.value, month.year)">Export</button></td>
            </tr>
            </tbody>
        </table>
    </div>
</template>

<script>
export default {
    name: "TaxInvoice",
    data: () => {
        return {
            is_busy: false,
            business: [],
            months: {},
            errors: {},
            is_succeded: false,
        }
    },
    mounted() {
        this.business = Business;
        this.months = Months;
    },
    methods: {
        async downloadInvoice(month, year){
            let submissionData = {
                'month': month,
                'year' : year
            };
            await axios.post(this.getDomain('business/' + this.business.id + '/fee-invoices/download', 'dashboard'), submissionData).then(({data}) => {
                this.is_succeded = true;
            });
        }
    },
    computed: {
        getThisYear(){
            let today = new Date();
            return today.getFullYear();
        }
    }
}
</script>
