<template>
  <div>
    <Bar
      v-if="loaded"
      :chart-options="chartOptions"
      :chart-data="chartData"
      :chart-id="chartId"
      :dataset-id-key="datasetIdKey"
      :plugins="plugins"
      :css-classes="cssClasses"
      :styles="styles"
      :width="width"
      :height="height"/>
  </div>
</template>

<script>
import { Bar } from 'vue-chartjs/legacy'
import ChartDataLabels from 'chartjs-plugin-datalabels';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ChartDataLabels)

export default {
  name: 'HorizontalBarChart',  
  components: { Bar, ChartDataLabels },
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
      default: 180
    },
    height: {
      type: Number,
      default: 200
    },
    cssClasses: {
      default: '',
      type: String
    },
    styles: {
      type: Object,
      default: () => {}
    },
    business_id: String
  },
  data() {
    return {
      chartData: {
        labels: [],
        datasets: [ {
          barPercentage: 0.6,
          categoryPercentage: 1,
          data: [],
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
              mirror: true,
              padding: 10
            },
            stacked: true,
          },
          y: {
            grid: {
              display: false,
              drawBorder: false,
            },
            position: 'left',
            barThickness: 8,
            maxBarThickness: 8,
            ticks: {
              padding: 30
            }
          }
        },
        plugins: {
          legend: {
            display: false
          },
          datalabels: {
            anchor: 'center',
            align: 'center',
            clamp: true,
            formatter: Math.round,
            font: {
                weight: 'bold'
            }
          }
        }
      },
      loaded: false,
      plugins: [],
      payment_method: []
    }
  },
  mounted() {
    this.getPaymentMethod();
  },
  methods: {
    getPaymentMethod() {
      this.loaded = false;
      axios.get(this.getDomain(`v1/business/${this.business_id}/charge/report/payment-method`, 'api'), {
          withCredentials: true
      }).then(response => {
        console.log(response);
        let data = response.data.data;
        data.forEach(item => {
          this.chartData.labels.push(item.percentage + ' %');
          this.chartData.datasets[0].data.push(item.number);
        });
        this.loaded = true;
      });
    }
  }
}
</script>
<style lang="scss">
.payment_method {
  width: 20%;
  float: left;
  .payment {
    width: 100%;
    float: left;
    margin: 5px 0 5px 0;
    .icon_payment {
      width: 25px;
      height: 25px;
    }
  }
}

.chart{
  width: 78%;
  float: left;
}
</style>