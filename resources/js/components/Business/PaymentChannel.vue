<template>
    <div class="widget-chart doughnut">
        <div class="card h-100">
            <div class="card-body">
                <div class="top-title d-flex justify-content-between align-items-center">
                    <h5 class="title">Payment channel</h5>
                    <span>Past 7 days</span>
                </div>
                <div class="main-chart">
                    <DoughnutChart 
                        :business_id="business_id"/>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import DoughnutChart from './DoughnutChart'
export default {
    name: 'PaymentChannel',
    components: {
        DoughnutChart
    },
    props: {
        business_id: String
    }
}
</script>