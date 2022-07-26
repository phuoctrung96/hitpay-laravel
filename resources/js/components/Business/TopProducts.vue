<template>
    <div class="widget-chart">
        <div class="card h-100">
            <div class="card-body">
                <div class="top-title d-flex justify-content-between align-items-center">
                    <h5 class="title">Top products</h5>
                </div>
                <div class="main-chart">
                    <div class="row row-chart">
                        <div class="col title-product">
                            <div v-for="(name, index ) in data_label" :key="index" class="d-flex item">
                                <div :style="style(index)" class="circle"></div>
                                <span>{{name}}</span>
                            </div>
                        </div>
                        <div class="col col-chart">
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
        </div>
    </div>
</template>

<script>
import { Bar } from 'vue-chartjs/legacy'
import ChartDataLabels from 'chartjs-plugin-datalabels';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

export default {
    name: 'TopProducts',
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
            default: 150
        },
        height: {
            type: Number,
            default: 230
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
        data_label: {
            type: Array,
            default: () => {}
        },
        data_percent: {
            type: Array,
            default: () => {}
        },
        data_total: {
            type: Array,
            default: () => {}
        }
    },
    data() {
        return {
            chartData: {
                labels: this.data_percent,
                datasets: [ {
                    barPercentage: 0.6,
                    categoryPercentage: 1,
                    data: this.data_total,
                    backgroundColor: '#EDF3FF',
                    pointBackgroundColor: 'white',
                    borderWidth: {
                        top: 0,
                        right: 0,
                        bottom: 0,
                        left: 1,
                    },
                    borderSkipped: false,
                    borderColor: '#002771',
                    barThickness: 22,  
                    }
                ],
            },
            chartOptions: {
                maintainAspectRatio: false,
                anchor: 'center',
                indexAxis: 'y',
                responsive: false,
                legend: {
                    display: false
                },
                scales: {
                    x: {
                        display: false,
                        grid: {
                            display: false,
                            drawBorder: false,
                        },
                        ticks: {
                            autoSkip: false,
                            mirror: true
                        },
                        stacked: true,
                    },
                    y: {
                        grid: {
                            display: false,
                            drawBorder: false,
                        },
                        position: 'left',
                        barThickness: 20,
                        maxBarThickness: 20,
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    datalabels: {
                        anchor: 'start',
                        align: 'right',
                        clamp: false,
                        formatter: Math.round,
                        font: {
                            weight: 'bold'
                        }
                    }
                }
            }
        }
    },
    computed: {
        
    },
    methods: {
        style(index) {
            let colors = ['#673ab7', '#cfb844', '#cf44a3', '#44cf67', '#8344cf']
            return {
                backgroundColor: colors[index],
                width: '10px',
                height: '10px',
                borderRadius:'5px'
            };
        },
    }
}
</script>