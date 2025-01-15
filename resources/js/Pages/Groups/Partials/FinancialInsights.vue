<script setup lang="ts">
import { computed } from 'vue'
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle
} from "@/Components/ui/card"
import {formatCurrency} from "@/lib/formatters";

const props = defineProps({
  contributionInsights: {
    type: Object,
    default: () => ({})
  }
})

// Monthly Contribution Trend Chart
const monthlyContributionSeries = computed(() => [{
  name: 'Monthly Contributions',
  data: props.contributionInsights.monthly_contribution_trend?.map(item => item.total_amount) || []
}])

const monthlyContributionChartOptions = computed(() => ({
  chart: {
    type: 'line',
    height: 300,
    toolbar: { show: false }
  },
  responsive: [{
    breakpoint: 480,
    options: {
      chart: {
        width: '100%'
      }
    }
  }],
  xaxis: {
    categories: props.contributionInsights.monthly_contribution_trend?.map(
      item => `${item.year}-${item.month.toString().padStart(2, '0')}`
    ) || [],
    title: { text: 'Month' }
  },
  yaxis: {
    title: { text: 'Contribution Amount' },
    labels: {
      formatter: (value) => formatCurrency(value)
    }
  },
  tooltip: {
    theme: 'light',
    y: {
      formatter: (value) => formatCurrency(value)
    }
  }
}))

// Contribution Types Pie Chart
const contributionTypesSeries = computed(() =>
  props.contributionInsights.contribution_types?.map(item => item.total_amount) || []
)

const contributionTypesChartOptions = computed(() => ({
  labels: props.contributionInsights.contribution_types?.map(item => item.type) || [],
  chart: {
    type: 'pie',
    height: 300,
    toolbar: { show: false }
  },
  responsive: [{
    breakpoint: 480,
    options: {
      chart: {
        width: '100%'
      },
      legend: {
        position: 'bottom'
      }
    }
  }],
  tooltip: {
    y: {
      formatter: (value) => `$${value.toFixed(2)}`
    }
  }
}))
</script>

<template>
  <div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Monthly Contribution Trend -->
      <Card>
        <CardHeader>
          <CardTitle>Monthly Contribution Trend</CardTitle>
        </CardHeader>
        <CardContent>
          <apexchart
            type="line"
            :options="monthlyContributionChartOptions"
            :series="monthlyContributionSeries"
            class="w-full h-[300px]"
          />
        </CardContent>
      </Card>

      <!-- Contribution Types Breakdown -->
      <Card>
        <CardHeader>
          <CardTitle>Contribution Types</CardTitle>
        </CardHeader>
        <CardContent>
          <apexchart
            type="pie"
            :options="contributionTypesChartOptions"
            :series="contributionTypesSeries"
            class="w-full h-[300px]"
          />
        </CardContent>
      </Card>
    </div>
  </div>
</template>
