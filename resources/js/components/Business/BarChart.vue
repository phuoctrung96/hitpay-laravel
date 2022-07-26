<template>
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
    :height="height"
  />
</template>

<script>
import { Bar } from 'vue-chartjs/legacy'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

export default {
  name: 'BarChart',
  components: { Bar },
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
      default: 150
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
    business_id: String
  },
  data() {
    return {
      chartData: {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        datasets: [ { 
            data: [],  
            backgroundColor: 'rgba(0, 88, 252, 0.5)',
            pointBackgroundColor: 'white',
            borderWidth: 1,
            pointBorderColor: '#249EBF',  
          } 
        ],
      },
      chartOptions: {
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
              min: 0,
              stepSize: 100,
              reverse: false,
              beginAtZero: true
            }
            
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
  },
  mounted() {
    this.getSaleVolume();
  },
  methods: {
    async getSaleVolume() {
      this.loaded = false;
      await axios.get(this.getDomain(`v1/business/${this.business_id}/charge/report/daily`, 'api'), {
          withCredentials: true
      }).then(response => {
        let data = response.data.data;
        data.forEach(item => {
          if(item.date)
            this.chartData.datasets[0].data.push(parseInt(item.sum));
        });
        this.loaded = true;
      });
    }
  }
}
</script>