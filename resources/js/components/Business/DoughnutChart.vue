<template>
  <Doughnut
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
import { Doughnut } from 'vue-chartjs/legacy'
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement } from 'chart.js'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement)

export default {
  name: 'DoughnutChart',
  components: { Doughnut },
  props: {
    chartId: {
      type: String,
      default: 'doughnut-chart'
    },
    datasetIdKey: {
      type: String,
      default: 'label'
    },
    width: {
      type: Number,
      default: 280
    },
    height: {
      type: Number,
      default: 180
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
    business_id: {
      default: '',
      type: String
    }
  },
  data() {
    return {
      chartData: {
        labels: [],
        datasets: [
          {
            backgroundColor: ['#82E8FB', '#FFDE95', '#8CB4FF', '#FDC494',  '#CAA0F6'],
            data: [],
            cutout: '75%'
          }
        ]
      },
      chartOptions: {
        responsive: true,
        maintainAspectRatio: false,
        legend: {
          display: false
        },
        plugins: {
          datalabels: {
            display: false
          },
          legend: {
              display: true,
              position: 'right',
              align: 'center',
              labels: {
                usePointStyle: true,
                boxWidth: 8,
                padding: 18,
                font: {
                    size: 13,
                    weight: '500',
                },
                generateLabels(chart) {
                  const data = chart.data;
                  if (data.labels.length && data.datasets.length) {
                    const {labels: {pointStyle}} = chart.legend.options;
        
                    let total = 0;
                    chart.data.datasets[0].data.forEach(element => {
                        total+= element;
                    });

                    return data.labels.map((label, i) => {
                      const meta = chart.getDatasetMeta(0);
                      const style = meta.controller.getStyle(i);
                      
                      return {
                        text:  label + '     ' + Math.floor((chart.data.datasets[0].data[i] / total) * 100) +'%',
                        fillStyle: style.backgroundColor,
                        strokeStyle: style.borderColor,
                        lineWidth: style.borderWidth,
                        pointStyle: pointStyle,
                        hidden: !chart.getDataVisibility(i),
                        index: i
                      };
                    });
                  }
                  return [];
                }
              }
          },   
        }
      },
      loaded: false
    }
  },
  mounted() {
    this.getPaymentChannel();
  },
  methods: {
    async getPaymentChannel() {
      this.loaded = false
      await axios.get(this.getDomain(`v1/business/${this.business_id}/charge/report/channel`, 'api'), {
        withCredentials: true
      }).then(response => {
        let data = response.data.data;
        data.forEach(item => {
          this.chartData.labels.push(item.channel);
          this.chartData.datasets[0].data.push(item.percentage)
        });

        console.log("Channel");
        console.log(this.chartData.datasets[0].data);
        this.loaded = true;
      });
    }
  }
}
</script>