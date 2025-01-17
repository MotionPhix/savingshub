<script setup lang="ts">
import {computed} from 'vue'
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle
} from "@/Components/ui/card"
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from "@/Components/ui/table"
import {Badge} from "@/Components/ui/badge"
import {
  formatCurrency,
  calculatePercentage
} from '@/lib/formatters'

const props = defineProps({
  contributionInsights: {
    type: Object,
    default: () => ({})
  }
})

// Contribution Trend Series
const contributionTrendSeries = computed(() => [{
  name: 'Contributions',
  data: props.contributionInsights.monthly_contribution_trend?.map(item => item.total_amount) || []
}])

const contributionTrendChartOptions = computed(() => ({
  chart: {
    type: 'area',
    height: 350,
    toolbar: {show: false},
    zoom: {enabled: false}
  },
  dataLabels: {enabled: false},
  stroke: {
    curve: 'smooth',
    width: 2
  },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.7,
      opacityTo: 0.9,
      stops: [0, 100]
    }
  },
  xaxis: {
    categories: props.contributionInsights.monthly_contribution_trend?.map(
      item => `${item.year}-${item.month.toString().padStart(2, '0')}`
    ) || [],
    title: {text: 'Month'}
  },
  yaxis: {
    title: {text: 'Contribution Amount'},
    labels: {
      formatter: (value) => formatCurrency(value)
    }
  },
  tooltip: {
    theme: 'light',
    y: {
      formatter: (value) => formatCurrency(value)
    }
  },
  responsive: [
    {
      breakpoint: 640,
      options: {
        chart: {
          height: 250
        },
        xaxis: {
          labels: {
            rotate: -45,
            rotateAlways: true,
            style: {
              fontSize: '10px'
            }
          }
        }
      }
    }
  ]
}))

// Contribution Types Series
const contributionTypesSeries = computed(() =>
  props.contributionInsights.contribution_types?.map(item => item.total_amount) || []
)

const contributionTypesChartOptions = computed(() => ({
  labels: props.contributionInsights.contribution_types?.map(item => item.type) || [],
  chart: {
    type: 'pie',
    height: 350,
    toolbar: {show: false}
  },
  colors: [
    '#4CAF50', // Green
    '#2196F3', // Blue
    '#FFC107', // Amber
    '#9C27B0'  // Purple
  ],
  tooltip: {
    y: {
      formatter: (value) => formatCurrency(value)
    }
  },
  responsive: [
    {
      breakpoint: 640,
      options: {
        chart: {
          height: 250
        },
        xaxis: {
          labels: {
            rotate: -45,
            rotateAlways: true,
            style: {
              fontSize: '10px'
            }
          }
        }
      }
    }
  ]
}))

// Contribution Status Breakdown
const contributionStatusBreakdown = computed(() =>
  props.contributionInsights.contribution_status_breakdown || []
)

const totalContributionAmount = computed(() =>
  contributionStatusBreakdown.value.reduce((sum, status) => sum + status.total_amount, 0)
)

const contributionStatusSeries = computed(() =>
  contributionStatusBreakdown.value.map(status => status.count)
)

const contributionStatusRadialChartOptions = computed(() => ({
  labels: contributionStatusBreakdown.value.map(status => status.status),
  chart: {
    type: 'radialBar',
    height: 350,
    toolbar: {show: false}
  },
  plotOptions: {
    radialBar: {
      dataLabels: {
        name: {
          fontSize: '22px',
        },
        value: {
          fontSize: '16px',
        },
        total: {
          show: true,
          label: 'Total Contributions',
          formatter: () => contributionStatusBreakdown.value.length
        }
      }
    }
  },
  tooltip: {
    y: {
      formatter: (value) => `${value} contributions`
    }
  }
}))

// Status Badge Variant
const getStatusVariant = (status) => {
  switch (status.toLowerCase()) {
    case 'pending':
      return 'warning'
    case 'paid':
      return 'success'
    case 'overdue':
      return 'destructive'
    default:
      return 'secondary'
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Mobile-Friendly Grid Layout -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Contribution Trend -->
      <Card>
        <CardHeader>
          <CardTitle>Contribution Trend</CardTitle>
        </CardHeader>
        <CardContent>
          <apexchart
            type="area"
            :options="contributionTrendChartOptions"
            :series="contributionTrendSeries"
            class="w-full h-[250px] md:h-[350px]"
          />
        </CardContent>
      </Card>

      <!-- Contribution Types Breakdown -->
      <Card>
        <CardHeader>
          <CardTitle>Contribution Types Distribution</CardTitle>
        </CardHeader>
        <CardContent>
          <apexchart
            type="pie"
            :options="contributionTypesChartOptions"
            :series="contributionTypesSeries"
            class="w-full h-[250px] md:h-[350px]"
          />
        </CardContent>
      </Card>

      <!-- Contribution Status Breakdown -->
      <Card class="md:col-span-2">
        <CardHeader>
          <CardTitle>Contribution Status Breakdown</CardTitle>
        </CardHeader>
        <CardContent>
          <!-- Responsive Flex Container -->
          <div class="flex flex-col md:flex-row gap-4">
            <!-- Chart Container -->
            <div class="w-full md:w-1/2">
              <apexchart
                type="radialBar"
                :options="contributionStatusRadialChartOptions"
                :series="contributionStatusSeries"
                class="w-full h-[250px] md:h-[300px]"
              />
            </div>

            <!-- Mobile-Friendly Table -->
            <div class="w-full md:w-1/2 overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead class="w-1/4">Status</TableHead>
                    <TableHead class="w-1/4">Count</TableHead>
                    <TableHead class="w-1/4">Total</TableHead>
                    <TableHead class="w-1/4">%</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow
                    v-for="status in contributionStatusBreakdown"
                    :key="status.status"
                    class="hover:bg-muted/50 transition-colors"
                  >
                    <TableCell>
                      <Badge :variant="getStatusVariant(status.status)">
                        {{ status.status }}
                      </Badge>
                    </TableCell>
                    <TableCell>{{ status.count }}</TableCell>
                    <TableCell>
                      {{ formatCurrency(status.total_amount) }}
                    </TableCell>
                    <TableCell>
                      {{ calculatePercentage(status.total_amount, totalContributionAmount) }}
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Mobile-Only Summary Cards -->
    <div class="md:hidden space-y-4">
      <Card v-for="status in contributionStatusBreakdown" :key="status.status">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-sm font-medium">
            <Badge :variant="getStatusVariant(status.status)">
              {{ status.status }}
            </Badge>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">
            {{ formatCurrency(status.total_amount) }}
          </div>
          <p class="text-xs text-muted-foreground">
            {{ status.count }} contributions
            ({{ calculatePercentage(status.total_amount, totalContributionAmount) }})
          </p>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

<style scoped>
/* Additional responsive tweaks */
@media (max-width: 768px) {
  .apexcharts-canvas {
    width: 100% !important;
  }
}
</style>
