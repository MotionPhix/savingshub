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
import {Card, CardContent, CardFooter, CardHeader, CardTitle} from "@/Components/ui/card";
import {Button} from "@/Components/ui/button";
import AppLayout from "@/Layouts/AppLayout.vue";
import {Table, TableBody, TableCell, TableHead, TableHeader, TableRow} from "@/Components/ui/table";
import {Badge} from "@/Components/ui/badge";
import {
  Select,
  SelectTrigger,
  SelectValue,
  SelectContent,
  SelectItem
} from "@/Components/ui/select";

const props = defineProps({
  contributions: Object,
  contributionInsights: Object,
  activeGroup: Object
})

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

// Utility methods
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'MWK'
  }).format(amount)
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
</script>

<template>
  <AppLayout>
    <div class="mx-auto sm:px-4 py-6 space-y-6">
      <!-- Page Header -->
      <div class="flex justify-between items-center">
        <h1 class="text-2xl md:text-3xl font-bold">
          Contributions in {{ activeGroup.name }}
        </h1>
        <Button @click="navigateToCreateContribution">
          <PlusIcon class="mr-2 h-4 w-4"/>
          New Contribution
        </Button>
      </div>

      <!-- Contribution Insights -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Contributed</CardTitle>
            <WalletIcon class="h-4 w-4 text-muted-foreground"/>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">
              {{ formatCurrency(contributionInsights.total_contributed) }}
            </div>
            <p class="text-xs text-muted-foreground">
              {{ contributionInsights.total_contributions }} contributions
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Pending</CardTitle>
            <ClockIcon class="h-4 w-4 text-muted-foreground"/>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-yellow-600">
              {{ formatCurrency(contributionInsights.pending_total) }}
            </div>
            <p class="text-xs text-muted-foreground">
              {{ contributionInsights.pending_count }} pending
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Paid</CardTitle>
            <CheckCircleIcon class="h-4 w-4 text-muted-foreground"/>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-600">
              {{ formatCurrency(contributionInsights.paid_total) }}
            </div>
            <p class="text-xs text-muted-foreground">
              {{ contributionInsights.paid_count }} paid
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Overdue</CardTitle>
            <AlertCircleIcon class="h-4 w-4 text-muted-foreground"/>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-red-600">
              {{ formatCurrency(contributionInsights.overdue_total) }}
            </div>
            <p class="text-xs text-muted-foreground">
              {{ contributionInsights.overdue_count }} overdue
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Contributions Table -->
      <Card>
        <CardHeader>
          <div class="flex justify-between items-center">
            <CardTitle>Contribution History</CardTitle>
            <div class="flex items-center space-x-2">
              <!-- Filters -->
              <Select v-model="filters.type">
                <SelectTrigger class="w-[180px]">
                  <SelectValue placeholder="Contribution Type"/>
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Types</SelectItem>
                  <SelectItem value="regular">Regular</SelectItem>
                  <SelectItem value="extra">Extra</SelectItem>
                  <SelectItem value="makeup">Makeup</SelectItem>
                </SelectContent>
              </Select>

              <Select v-model="filters.status">
                <SelectTrigger class="w-[180px]">
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
          <!-- Mobile View -->
          <div class="md:hidden space-y-4">
            <Card
              v-for="contribution in contributions.data"
              :key="contribution.id"
              class="hover:bg-muted/50 transition-colors"
            >
              <CardHeader>
                <div class="flex justify-between items-center">
                  <CardTitle class="text-base">
                    {{ formatCurrency(contribution.amount) }}
                  </CardTitle>
                  <Badge :variant="getStatusVariant(contribution.status)">
                    {{ contribution.status }}
                  </Badge>
                </div>
              </CardHeader>
              <CardContent>
                <div class="flex justify-between">
                <span class="text-muted-foreground">
                  {{ contribution.type }} Contribution
                </span>
                  <span>{{ formatDate(contribution.contribution_date) }}</span>
                </div>
              </CardContent>
              <CardFooter>
                <Button
                  variant="outline"
                  size="sm"
                  @click="viewContributionDetails(contribution)"
                >
                  View Details
                </Button>
              </CardFooter>
            </Card>
          </div>

          <!-- Desktop Table -->
          <Table class="hidden md:table">
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
                <TableCell>
                  {{ formatDate(contribution.contribution_date) }}
                </TableCell>
                <TableCell>
                  {{ formatCurrency(contribution.amount) }}
                </TableCell>
                <TableCell class="capitalize">
                  {{ contribution.type }}
                </TableCell>
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
        </CardContent>
      </Card>
    </div>

    <!-- Contribution Analysis Section -->
    <div class="grid md:grid-cols-2 gap-6 mt-6">
      <!-- Contribution Types Pie Chart -->
      <Card>
        <CardHeader>
          <CardTitle>Contribution Types</CardTitle>
        </CardHeader>
        <CardContent>
          <apexchart
            type="pie"
            :options="{
            labels: contributionTypeChart.labels,
            colors: ['#4CAF50', '#2196F3', '#FFC107'],
            legend: { position: 'bottom' }
          }"
            :series="contributionTypeChart.series"
            height="350"
          />
        </CardContent>
      </Card>

      <!-- Contribution Status Radial Chart -->
      <Card>
        <CardHeader>
          <CardTitle>Contribution Status</CardTitle>
        </CardHeader>
        <CardContent>
          <apexchart
            type="radialBar"
            :options="{
            labels: contributionStatusChart.labels,
            plotOptions: {
              radialBar: {
                dataLabels: {
                  name: { fontSize: '22px' },
                  value: { fontSize: '16px' },
                  total: {
                    show: true,
                    label: 'Total Contributions'
                  }
                }
              }
            },
            colors: ['#FFC107', '#4CAF50', '#F44336']
          }"
            :series="contributionStatusChart.series"
            height="350"
          />
        </CardContent>
      </Card>
    </div>

    <!-- Pagination for Mobile -->
    <div class="md:hidden flex justify-between items-center mt-4">
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
        @click="loadPage(contributions.current_page + 1)"
      >
        Next
      </Button>
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
</style>
