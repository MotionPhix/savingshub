<script setup lang="ts">
import {ref, computed, watch} from 'vue'
import {router} from '@inertiajs/vue3'
import {
  PlusIcon,
  WalletIcon,
  ClockIcon,
  CheckCircleIcon,
  AlertCircleIcon,
  FilterIcon
} from 'lucide-vue-next'
import {Card, CardContent, CardFooter, CardHeader, CardTitle} from "@/Components/ui/card"
import {Button} from "@/Components/ui/button"
import AppLayout from "@/Layouts/AppLayout.vue"
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
  Select,
  SelectTrigger,
  SelectValue,
  SelectContent,
  SelectItem
} from "@/Components/ui/select"
import {
  Drawer,
  DrawerClose,
  DrawerContent,
  DrawerFooter,
  DrawerHeader,
  DrawerTitle,
  DrawerTrigger
} from "@/Components/ui/drawer"
import {formatCurrency} from "@/lib/formatters"
import PageHeader from "@/Components/PageHeader.vue";
import ContributionInsightCard from "@/Pages/Contributions/Partials/ContributionInsightCard.vue";
import ContributionMobileCard from "@/Pages/Contributions/Partials/ContributionMobileCard.vue";
import EmptyState from "@/Pages/Contributions/Partials/EmptyState.vue";
import EmptyChartState from "@/Pages/Contributions/Partials/EmptyChartState.vue";

const props = defineProps({
  contributions: Object,
  contributionInsights: Object,
  isAdminOrTreasurer: {
    type: Boolean,
    default: false
  },
})

// Enhanced Filters with Mobile Drawer
const isFilterDrawerOpen = ref(false)

// Filters state
const filters = ref({
  type: 'all',
  status: 'all',
  page: 1
})

// Watchers for filters
watch(filters.value, (newFilters) => {
  applyFilters()
})

// Filter application method
const applyFilters = () => {
  router.get(route('contributions.index'), {
    type: filters.value.type !== 'all' ? filters.value.type : undefined,
    status: filters.value.status !== 'all' ? filters.value.status : undefined,
    page: filters.value.page
  }, {
    preserveState: true,
    preserveScroll: true
  })
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Status badge variant
const getStatusVariant = (status) => {
  switch(status) {
    case 'pending': return 'warning'
    case 'paid': return 'success'
    case 'overdue': return 'destructive'
    default: return 'secondary'
  }
}

// Navigation Methods
const navigateToCreateContribution = () => {
  router.visit(route('contributions.create'))
}

const viewContributionDetails = (contribution) => {
  router.visit(route('contributions.show', contribution.uuid))
}

// Pagination
const loadPage = (page) => {
  filters.value.page = page
  applyFilters()
}

// Advanced Insights Computation
const advancedInsights = computed(() => ({
  contributionTrend: calculateContributionTrend(),
  projectedContributions: calculateProjectedContributions(),
  riskAssessment: assessContributionRisk()
}))

function calculateContributionTrend() {
  const contributions = props.contributions.data
  if (contributions.length < 2) return {trend: 'Insufficient Data', percentage: 0}

  const sortedContributions = contributions.sort((a, b) =>
    new Date(a.contribution_date) - new Date(b.contribution_date)
  )

  const firstContribution = sortedContributions[0].amount
  const lastContribution = sortedContributions[sortedContributions.length - 1].amount

  const trendPercentage = ((lastContribution - firstContribution) / firstContribution) * 100

  return {
    trend: trendPercentage >= 0 ? 'Increasing' : 'Decreasing',
    percentage: Math.abs(trendPercentage.toFixed(2))
  }
}

function calculateProjectedContributions() {
  const insights = props.contributionInsights
  const averageContribution = insights.total_contributed / insights.total_contributions

  return {
    nextMonthProjection: averageContribution * 1.1,
    nextQuarterProjection: averageContribution * 3 * 1.1
  }
}

function assessContributionRisk() {
  const insights = props.contributionInsights
  const overdueRatio = insights.overdue_count / insights.total_contributions

  return {
    level: overdueRatio > 0.3 ? 'High' : overdueRatio > 0.15 ? 'Medium' : 'Low',
    overduePercentage: (overdueRatio * 100).toFixed(2)
  }
}

function generatePageNumbers() {
  const current = props.contributions.current_page
  const last = props.contributions.last_page
  const delta = 2
  const left = current - delta
  const right = current + delta + 1
  const range = []
  const rangeWithDots = []
  let l

  for (let i = 1; i <= last; i++) {
    if (i === 1 || i === last || (i >= left && i < right)) {
      range.push(i)
    }
  }

  for (let i of range) {
    if (l) {
      if (i - l === 2) {
        rangeWithDots.push(l + 1)
      } else if (i - l !== 1) {
        rangeWithDots.push('...')
      }
    }
    rangeWithDots.push(i)
    l = i
  }

  return rangeWithDots
}

// Enhanced responsive styles
const responsiveStyles = {
  smallDevices: {
    gridTemplateColumns: 'repeat(1, 1fr)',
    padding: '0.5rem',
    fontSize: '0.875rem'
  },
  mediumDevices: {
    gridTemplateColumns: 'repeat(2, 1fr)',
    padding: '1rem',
    fontSize: '1rem'
  }
}

const compactInsights = computed(() => [
  {
    title: 'Total Contributed',
    value: formatCurrency(props.contributionInsights.total_contributed),
    subtitle: `${props.contributionInsights.total_contributions} contributions`,
    icon: WalletIcon,
    variant: 'default'
  },
  {
    title: 'Pending',
    value: formatCurrency(props.contributionInsights.pending_total),
    subtitle: `${props.contributionInsights.pending_count} pending`,
    icon: ClockIcon,
    variant: 'warning'
  },
  {
    title: 'Paid',
    value: formatCurrency(props.contributionInsights.paid_total),
    subtitle: `${props.contributionInsights.paid_count} paid`,
    icon: CheckCircleIcon,
    variant: 'success'
  },
  {
    title: 'Overdue',
    value: formatCurrency(props.contributionInsights.overdue_total),
    subtitle: `${props.contributionInsights.overdue_count} overdue`,
    icon: AlertCircleIcon,
    variant: 'destructive'
  }
])

// Contribution Type Charts
const contributionTypeChart = computed(() => ({
  labels: Object.keys(props.contributionInsights.contribution_types || {}),
  series: Object.values(props.contributionInsights.contribution_types || {})
}))

// Contribution Status Radial Chart
const contributionStatusChart = computed(() => ({
  labels: Object.keys(props.contributionInsights.status_breakdown || {}),
  series: Object.values(props.contributionInsights.status_breakdown || {})
}))

// Check for empty chart data
const hasContributionTypeData = computed(() =>
  contributionTypeChart.value.series.some(value => value > 0)
)

const hasContributionStatusData = computed(() =>
  contributionStatusChart.value.series.some(value => value > 0)
)

// Chart Options
const contributionTypeChartOptions = computed(() => ({
  labels: contributionTypeChart.value.labels,
  colors: ['#4CAF50', '#2196F3', '#FFC107'],
  legend: {
    position: 'bottom',
    itemMargin: {
      horizontal: 5,
      vertical: 5
    }
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
  }]
}))

const contributionStatusChartOptions = computed(() => ({
  labels: contributionStatusChart.value.labels,
  colors: ['#FFC107', '#4CAF50', '#F44336'],
  plotOptions: {
    radialBar: {
      dataLabels: {
        name: {
          fontSize: '16px',
          show: true
        },
        value: {
          fontSize: '12px',
          show: true
        },
        total: {
          show: true,
          label: 'Contributions',
          fontSize: '14px'
        }
      }
    }
  },
  responsive: [{
    breakpoint: 480,
    options: {
      chart: {
        height: 250
      },
      plotOptions: {
        radialBar: {
          dataLabels: {
            name: { fontSize: '12px' },
            value: { fontSize: '10px' }
          }
        }
      }
    }
  }]
}))
</script>

<template>
  <AppLayout>
    <div class="mx-auto sm:px-4 space-y-6 my-6">
      <!-- Page Header -->
      <PageHeader>
        Contributions in {{ $page.props.current_group.name }}'s

        <template #description>
          Manage and track your group contributions
        </template>

        <template #action>
          <Button
            @click="navigateToCreateContribution"
            class="w-full sm:w-auto">
            <PlusIcon class="mr-2 h-4 w-4"/>
            New Contribution
          </Button>
        </template>
      </PageHeader>

      <!-- Insights Section with Advanced Analytics -->
      <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <ContributionInsightCard
          v-for="(insight, key) in compactInsights"
          :key="key"
          :title="insight.title"
          :value="insight.value"
          :subtitle="insight.subtitle"
          :icon="insight.icon"
          :variant="insight.variant"
        />
      </div>

      <!-- Responsive Contribution List with Drawer Filters -->
      <Card>
        <CardHeader>
          <div class="flex justify-between items-center">
            <CardTitle>Contribution History</CardTitle>

            <!-- Mobile Filter Drawer -->
            <Drawer v-model:open="isFilterDrawerOpen">
              <DrawerTrigger as-child class="sm:hidden">
                <Button variant="outline" size="sm">
                  <FilterIcon class="h-4 w-4 mr-2"/>
                  Filters
                </Button>
              </DrawerTrigger>
              <DrawerContent>
                <DrawerHeader>
                  <DrawerTitle>Filter Contributions</DrawerTitle>
                </DrawerHeader>

                <!-- Filter Contents -->
                <div class="grid gap-4 p-4">
                  <Select v-model="filters.type">
                    <SelectTrigger>
                      <SelectValue placeholder="Contribution Type"/>
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">All Types</SelectItem>
                      <SelectItem value="regular">Regular</SelectItem>
                      <SelectItem value="extra">Extra</SelectItem>
                    </SelectContent>
                  </Select>

                  <Select v-model="filters.status">
                    <SelectTrigger>
                      <SelectValue placeholder="Contribution Status"/>
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">All Statuses</SelectItem>
                      <SelectItem value="pending">Pending</SelectItem>
                      <SelectItem value="paid">Paid</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <DrawerFooter>
                  <Button @click="applyFilters">Apply Filters</Button>
                  <DrawerClose>
                    <Button variant="outline">Cancel</Button>
                  </DrawerClose>
                </DrawerFooter>
              </DrawerContent>
            </Drawer>

            <div class="hidden sm:flex sm:space-x-2">
              <Select v-model="filters.type" class="w-full sm:w-[180px]">
                <SelectTrigger>
                  <SelectValue placeholder="Contribution Type"/>
                </SelectTrigger>

                <SelectContent align="end">
                  <SelectItem value="all">All Types</SelectItem>
                  <SelectItem value="regular">Regular</SelectItem>
                  <SelectItem value="extra">Extra</SelectItem>
                  <SelectItem value="makeup">Makeup</SelectItem>
                </SelectContent>
              </Select>

              <Select v-model="filters.status" class="w-full sm:w-[180px]">
                <SelectTrigger>
                  <SelectValue placeholder="Status"/>
                </SelectTrigger>

                <SelectContent align="end">
                  <SelectItem value="all">All Statuses</SelectItem>
                  <SelectItem value="pending">Pending</SelectItem>
                  <SelectItem value="paid">Paid</SelectItem>
                  <SelectItem value="overdue">Overdue</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        </CardHeader>

        <CardContent>
          <!-- Empty State -->
          <EmptyState
            v-if="contributions.data.length === 0"
            title="No Contributions Yet"
            description="Start tracking your group contributions"
            illustration="empty-contributions" :can_create_group="true">
            <Button @click="navigateToCreateContribution">
              <PlusIcon class="mr-2 h-4 w-4"/>
              Add First Contribution
            </Button>
          </EmptyState>

          <!-- Contributions List -->
          <template v-else>
            <!-- Mobile View -->
            <div class="block sm:hidden space-y-2">
              <ContributionMobileCard
                v-for="contribution in contributions.data"
                :key="contribution.id"
                :contribution="contribution"
                @view="viewContributionDetails"
              />
            </div>

            <!-- Desktop Table -->
            <Table class="hidden sm:table">
              <TableHeader>
                <TableRow>
                  <TableHead v-if="isAdminOrTreasurer">Member</TableHead>
                  <TableHead>Date</TableHead>
                  <TableHead>Amount</TableHead>
                  <TableHead>Type</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead />
                </TableRow>
              </TableHeader>

              <TableBody>
                <TableRow
                  v-for="contribution in contributions.data"
                  :key="contribution.id"
                  class="hover:bg-muted/50 transition-colors">
                  <TableCell v-if="isAdminOrTreasurer" class="font-medium">
                    {{ contribution.user?.name || 'Unknown' }}
                  </TableCell>
                  <TableCell>{{ formatDate(contribution.contribution_date) }}</TableCell>
                  <TableCell>{{ formatCurrency(contribution.amount) }}</TableCell>
                  <TableCell class="capitalize">{{ contribution.type }}</TableCell>
                  <TableCell>
                    <Badge class="capitalize" :variant="getStatusVariant(contribution.status)">
                      {{ contribution.status }}
                    </Badge>
                  </TableCell>

                  <TableCell align="end">
                    <Button
                      variant="outline"
                      size="sm"
                      @click="viewContributionDetails(contribution)">
                      View
                    </Button>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </template>
        </CardContent>
      </Card>

      <!-- Analysis Section with Empty States -->
      <div class="grid sm:grid-cols-2 gap-4">
        <!-- Contribution Types Chart -->
        <Card>
          <CardHeader>
            <CardTitle>Contribution Types</CardTitle>
          </CardHeader>

          <CardContent>
            <EmptyChartState
              v-if="!hasContributionTypeData"
              title="No Contribution Types Data"
              description="Start making contributions to see your breakdown"
            />

            <apexchart
              v-else
              type="pie"
              :options="contributionTypeChartOptions"
              :series="contributionTypeChart.series"
              height="350"
            />
          </CardContent>
        </Card>

        <!-- Contribution Status Chart -->
        <Card>
          <CardHeader>
            <CardTitle>Contribution Status</CardTitle>
          </CardHeader>

          <CardContent>
            <EmptyChartState
              v-if="!hasContributionStatusData"
              title="No Contribution Status Data"
              description="Your contribution status will appear here"
            />

            <apexchart
              v-else
              type="radialBar"
              :options="contributionStatusChartOptions"
              :series="contributionStatusChart.series"
              height="350"
            />
          </CardContent>
        </Card>
      </div>

      <!-- Risk Assessment Section -->
      <Card>
        <CardHeader>
          <CardTitle>Contribution Risk Assessment</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-background p-4 rounded-lg border">
              <div class="flex justify-between items-center mb-2">
                <h3 class="text-sm font-medium">Risk Level</h3>
                <Badge
                  :variant="
                    advancedInsights.riskAssessment.level === 'High' ? 'destructive' :
                    advancedInsights.riskAssessment.level === 'Medium' ? 'warning' : 'success'
                  ">
                  {{ advancedInsights.riskAssessment.level }}
                </Badge>
              </div>

              <p class="text-xs text-muted-foreground">
                Overdue Contribution Percentage
              </p>

              <div class="text-lg font-bold">
                {{ advancedInsights.riskAssessment.overduePercentage }}%
              </div>
            </div>

            <div class="bg-background p-4 rounded-lg border">
              <h3 class="text-sm font-medium mb-2">Projected Contributions</h3>

              <div class="space-y-2">
                <div>
                  <p class="text-xs text-muted-foreground">Next Month</p>
                  <div class="text-lg font-bold">
                    {{ formatCurrency(advancedInsights.projectedContributions.nextMonthProjection) }}
                  </div>
                </div>

                <div>
                  <p class="text-xs text-muted-foreground">Next Quarter</p>
                  <div class="text-lg font-bold">
                    {{ formatCurrency(advancedInsights.projectedContributions.nextQuarterProjection) }}
                  </div>
                </div>
              </div>
            </div>

            <div class="bg-background p-4 rounded-lg border">
              <h3 class="text-sm font-medium mb-2">Contribution Trend</h3>
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-xs text-muted-foreground">Direction</p>
                  <div class="text-lg font-bold">
                    {{ advancedInsights.contributionTrend.trend }}
                  </div>
                </div>
                <div>
                  <p class="text-xs text-muted-foreground">Change</p>
                  <div class="text-lg font-bold">
                    {{ advancedInsights.contributionTrend.percentage }}%
                  </div>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Responsive Pagination -->
      <div class="flex flex-col sm:flex-row justify-between items-center mt-4 mb-6 space-y-2 sm:space-y-0">
        <div class="text-sm text-muted-foreground">
          Showing
          {{ (contributions.current_page - 1) * contributions.per_page + 1 }}-
          {{ Math.min(contributions.current_page * contributions.per_page, contributions.total) }}
          of {{ contributions.total }} contributions
        </div>

        <div class="flex items-center space-x-2">
          <Button
            variant="outline"
            size="sm"
            :disabled="contributions.current_page === 1"
            @click="loadPage(contributions.current_page - 1)"
          >
            Previous
          </Button>

          <div class="hidden sm:flex items-center space-x-1">
            <template v-for="page in generatePageNumbers()" :key="page">
              <Button
                :variant="page === contributions.current_page ? 'default' : 'outline'"
                size="sm"
                @click="loadPage(page)"
              >
                {{ page }}
              </Button>
            </template>
          </div>

          <Button
            variant="outline"
            size="sm"
            :disabled="contributions.current_page === contributions.last_page"
            @click="loadPage(contributions.current_page + 1)"
          >
            Next
          </Button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
@media (max-width: 640px) {
  .grid {
    grid-template-columns: 1fr !important;
    gap: 0.5rem !important;
  }

  .text-2xl {
    font-size: 1.25rem !important;
  }

  .card-content {
    padding: 0.5rem !important;
  }
}

/* Ultra-small device adjustments */
@media (max-width: 375px) {
  .container {
    padding: 0.25rem !important;
  }

  .text-lg {
    font-size: 1rem !important;
  }

  .card {
    border-radius: 0.5rem !important;
  }
}
</style>
