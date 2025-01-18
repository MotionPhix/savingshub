<script setup lang="ts">
import {ref, computed, watch} from 'vue'
import {router} from '@inertiajs/vue3'
import {
  PlusIcon,
  WalletIcon,
  ClockIcon,
  CheckCircleIcon,
  AlertCircleIcon
} from 'lucide-vue-next'
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  CardTitle
} from "@/Components/ui/card";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from "@/Components/ui/table";
import {
  Select,
  SelectTrigger,
  SelectValue,
  SelectContent,
  SelectItem
} from "@/Components/ui/select";
import {Badge} from "@/Components/ui/badge";
import {Button} from "@/Components/ui/button";
import AppLayout from "@/Layouts/AppLayout.vue";

// Components
import EmptyState from "@/Pages/Contributions/Partials/EmptyState.vue";
import ContributionInsightCard from "@/Pages/Contributions/Partials/ContributionInsightCard.vue";
import ContributionMobileCard from "@/Pages/Contributions/Partials/ContributionMobileCard.vue";
import EmptyChartState from "@/Pages/Contributions/Partials/EmptyChartState.vue";
import PageHeader from "@/Components/PageHeader.vue";

const props = defineProps({
  contributions: Object,
  contributionInsights: Object,
  activeGroup: Object
})

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

// Filters and Pagination
const filters = ref({
  type: 'all',
  status: 'all',
  page: 1
})

// Chart Data Computeds
const contributionTypeChart = computed(() => ({
  labels: Object.keys(props.contributionInsights.contribution_types || {}),
  series: Object.values(props.contributionInsights.contribution_types || {})
}))

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

// Utility Methods
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'MWK',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount)
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

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

// Filter Application
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
</script>

<template>
  <AppLayout>
    <div class="mx-auto sm:px-2 py-4 space-y-4 my-12">
      <!-- Page Header -->
      <PageHeader>
        Contributions in {{ activeGroup.name }}

        <template #action>
          <Button
            @click="navigateToCreateContribution"
            class="w-full sm:w-auto">
            <PlusIcon class="mr-2 h-4 w-4"/>
            New Contribution
          </Button>
        </template>
      </PageHeader>

      <!-- Insights Grid - More Compact on Small Screens -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-4">
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

      <!-- Contributions Section -->
      <Card class="w-full">
        <CardHeader>
          <div class="flex flex-col sm:flex-row justify-between items-center space-y-2 sm:space-y-0">
            <CardTitle>Contribution History</CardTitle>
            <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2 w-full sm:w-auto">
              <Select v-model="filters.type" class="w-full sm:w-[180px]">
                <SelectTrigger>
                  <SelectValue placeholder="Contribution Type"/>
                </SelectTrigger>
                <SelectContent>
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
                <SelectContent>
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
            illustration="empty-contributions"
          >
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
                  <TableHead>Date</TableHead>
                  <TableHead>Amount</TableHead>
                  <TableHead>Type</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow
                  v-for="contribution in contributions.data"
                  :key="contribution.id"
                  class="hover:bg-muted/50 transition-colors"
                >
                  <TableCell>{{ formatDate(contribution.contribution_date) }}</TableCell>
                  <TableCell>{{ formatCurrency(contribution.amount) }}</TableCell>
                  <TableCell class="capitalize">{{ contribution.type }}</TableCell>
                  <TableCell>
                    <Badge :variant="getStatusVariant(contribution.status)">
                      {{ contribution.status }}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <Button
                      variant="outline"
                      size="sm"
                      @click="viewContributionDetails(contribution)"
                    >
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

      <!-- Pagination -->
      <div class="flex justify-between items-center mt-4">
        <Button
          variant="outline"
          size="sm"
          :disabled="!contributions.prev_page_url"
          @click="loadPage(contributions.current_page - 1)"
        >
          Previous
        </Button>

        <span class="text-muted-foreground">
          Page {{ contributions.current_page }} of {{ contributions.last_page }}
        </span>

        <Button
          variant="outline"
          size="sm"
          :disabled="!contributions.next_page_url"
          @click="loadPage(contributions.current_page + 1)">
          Next
        </Button>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
/* Responsive adjustments */
@media (max-width: 768px) {
  .grid {
    grid-template-columns: 1fr !important;
  }
}
  /* Responsive adjustments */
@media (max-width: 480px) {
  .container {
    padding-left: 0.5rem;
    padding-right: 0.5rem;
  }

  .grid {
    gap: 0.5rem;
  }

  h1 {
    font-size: 1rem;
  }
}
</style>
