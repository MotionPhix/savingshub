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

const props = defineProps({
  contributions: Object,
  contributionInsights: Object,
  activeGroup: Object
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
  switch (status) {
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

// Navigation methods
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
</script>

<template>
  <AppLayout>
    <div class="container mx-auto px-2 sm:px-4 space-y-6 my-6">
      <!-- Responsive Page Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 class="text-xl sm:text-2xl md:text-3xl font-bold">
            {{ activeGroup.name }} Contributions
          </h1>
          <p class="text-muted-foreground text-sm mt-1">
            Manage and track your group contributions
          </p>
        </div>

        <Button @click="navigateToCreateContribution" class="w-full sm:w-auto">
          <PlusIcon class="mr-2 h-4 w-4"/>
          New Contribution
        </Button>
      </div>

      <!-- Insights Section with Advanced Analytics -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <!-- Existing Insight Cards -->
        <!-- Add tooltips or expandable details -->
        <Card class="hover:shadow-md transition-all">
          <CardHeader>
            <div class="flex justify-between items-center">
              <CardTitle class="text-xs sm:text-sm">Total Contributed</CardTitle>
              <WalletIcon class="h-4 w-4 text-muted-foreground"/>
            </div>
          </CardHeader>
          <CardContent>
            <div class="text-lg sm:text-2xl font-bold font-figures">
              {{ formatCurrency(contributionInsights.total_contributed) }}
            </div>
            <div class="text-xs text-muted-foreground flex justify-between">
              <span>{{ contributionInsights.total_contributions }} contributions</span>
              <Badge variant="secondary" class="text-[10px]">
                {{ advancedInsights.contributionTrend.trend }}
                {{ advancedInsights.contributionTrend.percentage }}%
              </Badge>
            </div>
          </CardContent>
        </Card>

        <!-- Similar enhancements for other insight cards -->
      </div>

      <!-- Responsive Contribution List with Drawer Filters -->
      <Card>
        <CardHeader>
          <div class="flex justify-between items-center">
            <CardTitle>Contribution History</CardTitle>

            <!-- Mobile Filter Drawer -->
            <Drawer v-model:open="isFilterDrawerOpen">
              <DrawerTrigger as-child>
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
          </div>
        </CardHeader>

        <!-- Rest of the contributions list remains similar -->
      </Card>

      <!-- Risk Assessment Section -->
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
            "
                >
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
