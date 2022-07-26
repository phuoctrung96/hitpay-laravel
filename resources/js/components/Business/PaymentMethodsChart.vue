<template>
    <div class="widget-chart h-100">
        <div class="card">
            <div class="card-body">
                <div class="top-title d-flex justify-content-between align-items-center">
                    <h5 class="title">Payment methods</h5>
                    <span>Past 7 days</span>
                </div>
                <div class="main-chart">
                    <HorizontalBarChart 
                        :business_id="business_id"/>
                </div>
                <div class="enable">
                    <a href="#">Enable now</a>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import HorizontalBarChart from './HorizontalBarChart'
export default {
    name: 'PaymentMethodsChart',
    components: {
        HorizontalBarChart
    },
    props: {
        business_id: String
    }
}
</script>