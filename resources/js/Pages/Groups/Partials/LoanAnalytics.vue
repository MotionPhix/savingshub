<script setup lang="ts">
import { computed } from 'vue'
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
import { formatCurrency } from '@/lib/formatters'

const props = defineProps({
  loanInsights: {
    type: Object,
    default: () => ({})
  }
})

// Monthly Loan Trend Series
const monthlyLoanTrendSeries = computed(() => [{
  name: 'Monthly Loan Amount',
  data: props.loanInsights.monthly_loan_trend?.map(item => item.total_amount) || []
}])

const monthlyLoanTrendChartOptions = computed(() => ({
  chart: {
    type: 'bar',
    height: 300,
    toolbar: { show: false }
  },
  plotOptions: {
    bar: {
      horizontal: false,
      columnWidth: '55%',
      endingShape: 'rounded'
    }
  },
  dataLabels: {
    enabled: false
  }, xaxis: {
    categories: props.loanInsights.monthly_loan_trend?.map(
      item => `${item.year}-${item.month.toString().padStart(2, '0')}`
    ) || [],
    title: { text: 'Month' }
  },
  yaxis: {
    title: { text: 'Loan Amount' },
    labels: {
      formatter: (value) => `$${value.toFixed(2)}`
    }
  },
  tooltip: {
    theme: 'light',
    y: {
      formatter: (value) => `$${value.toFixed(2)}`
    }
  }
}))

// Loan Status Donut Chart
const loanStatusSeries = computed(() =>
  props.loanInsights.loan_status_distribution?.map(item => item.count) || []
)

const loanStatusChartOptions = computed(() => ({
  labels: props.loanInsights.loan_status_distribution?.map(item => item.status) || [],
  chart: {
    type: 'donut',
    height: 300,
    toolbar: { show: false }
  },
  tooltip: {
    y: {
      formatter: (value) => `${value} loans`
    }
  }
}))

// Top Borrowers Data
const topBorrowers = computed(() => props.loanInsights.top_borrowers || [])

// Calculate Repayment Percentage
const calculateRepaymentPercentage = (borrower) => {
  const totalBorrowed = borrower.total_borrowed || 0
  const totalRepaid = borrower.total_repaid || 0
  return totalBorrowed > 0
    ? `${((totalRepaid / totalBorrowed) * 100).toFixed(2)}%`
    : '0.00%'
}
</script>

<template>
  <div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Monthly Loan Trend -->
      <Card>
        <CardHeader>
          <CardTitle>Monthly Loan Trend</CardTitle>
        </CardHeader>
        <CardContent>
          <apexchart
            type="bar"
            :options="monthlyLoanTrendChartOptions"
            :series="monthlyLoanTrendSeries"
            class="w-full h-[300px]"
          />
        </CardContent>
      </Card>

      <!-- Loan Status Distribution -->
      <Card>
        <CardHeader>
          <CardTitle>Loan Status Distribution</CardTitle>
        </CardHeader>
        <CardContent>
          <apexchart
            type="donut"
            :options="loanStatusChartOptions"
            :series="loanStatusSeries"
            class="w-full h-[300px]"
          />
        </CardContent>
      </Card>

      <!-- Top Borrowers -->
      <Card class="md:col-span-2">
        <CardHeader>
          <CardTitle>Top Borrowers</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Loan Count</TableHead>
                  <TableHead>Total Borrowed</TableHead>
                  <TableHead>Total Repaid</TableHead>
                  <TableHead>Repayment Percentage</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow
                  v-for="borrower in topBorrowers"
                  :key="borrower.id"
                >
                  <TableCell>{{ borrower.name }}</TableCell>
                  <TableCell>{{ borrower.loan_count }}</TableCell>
                  <TableCell>
                    {{ formatCurrency(borrower.total_borrowed) }}
                  </TableCell>
                  <TableCell>
                    {{ formatCurrency(borrower.total_repaid) }}
                  </TableCell>
                  <TableCell>
                    {{ calculateRepaymentPercentage(borrower) }}
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
