<template>
    <div class="widget-chart">
        <div class="card h-100">
            <div class="card-body">
                <div class="top-title d-flex justify-content-between align-items-center">
                    <h5 class="title">Sale volume</h5>
                    <select class="form-control is-custom-select is-dropdown">
                        <option value="1" selected>This week</option>
                    </select>
                </div>
                <div class="main-chart">
                    <BarChart 
                        :business_id="business_id"/>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import BarChart from './BarChart'
export default {
    name: 'SaleVolume',
    components: {
        BarChart
    },
    props: {
        business_id: String
    }
}
</script>