<template>
    <div class="widget-chart">
        <div class="card h-100">
            <div class="card-body">
                <div class="top-title d-flex justify-content-between align-items-center">
                    <h5 class="title">Total Orders</h5>
                    <select class="form-control is-custom-select is-dropdown">
                        <option value="1" selected>This week</option>
                    </select>
                </div>
                <div class="main-chart">
                    <Bar
                        :chart-options="chartOptions"
                        :chart-data="chartData"
                        :chart-id="chartId"
                        :dataset-id-key="datasetIdKey"
                        :plugins="plugins"
                        :css-classes="cssClasses"
                        :styles="styles"
                        :width="width"
                        :height="height"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { Bar } from 'vue-chartjs/legacy'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

export default {
    name: 'TotalOrders',
    components: {
        Bar
    },
    props: {
        chartId: {
            type: String,
            default: 'bar-chart'
        },
        datasetIdKey: {
            type: String,
            default: 'label'
        },
        width: {
            type: Number,
            default: 200
        },
        height: {
            type: Number,
            default: 220
        },
        cssClasses: {
            default: '',
            type: String
        },
        styles: {
            type: Object,
            default: () => {}
        },
        plugins: {
            type: Object,
            default: () => {}
        },
        business_id: String,
        data_order: {
            type: Array,
            default: () => {}
        },
        data_order_label: {
            type: Array,
            default: () => {}
        },
        data_max: {
            type: Number,
            default: 0
        }
    },
    data() {
        return {
            chartData: {
                labels: this.data_order_label,
                datasets: [ { 
                    data: this.data_order,  
                    backgroundColor: 'rgba(0, 88, 252, 0.5)',
                    pointBackgroundColor: 'white',
                    borderWidth: 1,
                    pointBorderColor: '#249EBF',
                    maxBarThickness: 40,  
                } 
                ],
            },
            chartOptions: {
                maintainAspectRatio: false,
                responsive: true,
                cutout: '90%',
                legend: {
                display: false
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            autoSkip: false
                        }
                    },
                    y: {
                        grid: {
                            display: false,
                            drawBorder: false,
                        },
                        position: 'right',
                        ticks: {
                            beginAtZero: true,
                            min: 0
                        },
                        max: this.data_max
                    }
                },
                plugins: {
                legend: {
                    display: false
                },
                datalabels: {
                    display: false,
                    anchor: 'end',
                    align: 'end',
                    formatter: (val) => ('$' + val)
                }
                }
            },
            loaded: false
        }
    }
}
</script>