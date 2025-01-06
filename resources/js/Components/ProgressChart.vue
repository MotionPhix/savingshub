<script setup>
import { ref, computed, watch } from 'vue'
import VueApexCharts from 'vue3-apexcharts'

const props = defineProps({
  data: {
    type: Array,
    required: true
  },
  title: {
    type: String,
    default: 'Savings Progress'
  },
  type: {
    type: String,
    default: 'area',
    validator: (value) => ['line', 'area', 'bar', 'radar'].includes(value)
  },
  height: {
    type: Number,
    default: 350
  },
  colors: {
    type: Array,
    default: () => ['#4CAF50', '#2196F3', '#FF9800']
  }
})

// Compute series data
const series = computed(() => [
  {
    name: 'Savings',
    data: props.data.map(item => item.value)
  }
])

// Dynamic chart options
const chartOptions = computed(() => ({
  chart: {
    type: props.type,
    toolbar: { show: false },
    zoom: { enabled: false }
  },
  title: {
    text: props.title,
    align: 'left',
    style: {
      fontSize: '16px',
      fontWeight: 'bold'
    }
  },
  colors: props.colors,
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.7,
      opacityTo: 0.3,
      stops: [0, 90, 100]
    }
  },
  dataLabels: { enabled: false },
  stroke: {
    curve: 'smooth',
    width: 2
  },
  xaxis: {
    categories: props.data.map(item => item.label),
    labels: {
      style: { colors: '#888' }
    }
  },
  yaxis: {
    labels: {
      formatter: (value) => `$${value.toFixed(2)}`,
      style: { colors: '#888' }
    },
    title: {
      text: 'Amount',
      style: { color: '#888' }
    }
  },
  tooltip: {
    theme: 'light',
    x: { show: true },
    y: {
      formatter: (value) => `$${value.toFixed(2)}`,
      title: { formatter: () => 'Savings: ' }
    }
  },
  grid: {
    borderColor: '#f1f1f1',
    strokeDashArray: 7
  },
  responsive: [
    {
      breakpoint: 480,
      options: {
        chart: {
          height: 250
        }
      }
    }
  ]
}))
</script>

<template>
  <div class="progress-chart">
    <apexchart
      :type="type"
      :height="height"
      :options="chartOptions"
      :series="series"
    />
  </div>
</template>

<style scoped>
.progress-chart {
  width: 100%;
  height: 100%;
}
</style>
