<script setup>
import { ref } from 'vue'
import {
  WalletIcon,
  ReceiptIcon
} from 'lucide-vue-next'
import {
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent
} from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import { ScrollArea } from '@/Components/ui/scroll-area'

const props = defineProps({
  user: Object,
  groups: Array,
  dashboardData: Object
})

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: props.currency || 'MWK'
  }).format(amount)
}

const getBalanceClass = (balance) => {
  return balance >= 0
    ? 'text-green-600 font-semibold'
    : 'text-destructive font-semibold'
}
</script>

<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold mb-6">Financial Overview</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <Card>
        <CardHeader>
          <CardTitle>Group Financial Summary</CardTitle>
          <CardDescription>Overview of group finances</CardDescription>
        </CardHeader>
        <CardContent>
          <div
            v-for="overview in dashboardData.financialOverview"
            :key="overview.groupName"
            class="mb-4 p-4 bg-muted/50 rounded-lg"
          >
            <div class="flex flex-col sm:flex-row justify-between items-center">
              <h3 class="text-lg font-semibold mb-2 sm:mb-0">
                {{ overview.groupName }}
              </h3>
              <div class="flex flex-col sm:flex-row gap-2">
                <Badge variant="secondary">
                  Contributions: {{ formatCurrency(overview.totalContributions) }}
                </Badge>
                <Badge variant="destructive">
                  Loans: {{ formatCurrency(overview.totalLoans) }}
                </Badge>
              </div>
            </div>
            <div class="mt-2 text-sm text-muted-foreground">
              Available Balance:
              <span :class="getBalanceClass(overview.availableBalance)">
                {{ formatCurrency(overview.availableBalance) }}
              </span>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Upcoming Financial Activities</CardTitle>
          <CardDescription>Pending financial transactions</CardDescription>
        </CardHeader>
        <CardContent>
          <ScrollArea class="h-[300px]">
            <div v-if="dashboardData.upcomingFinancialActivities.length === 0"
                 class="text-muted-foreground text-center py-4">
              No upcoming financial activities
            </div>
            <div
              v-for="activity in dashboardData.upcomingFinancialActivities"
              :key="activity.id"
              class="mb-3 p-3 bg-background border rounded-lg"
            >
              <div class="flex justify-between items-center">
                <div>
                  <h4 class="font-medium">
                    {{ activity.type === 'loan' ? 'Loan Request' : 'Contribution' }}
                  </h4>
                  <p class="text-sm text-muted-foreground">
                    {{ activity.groupName }}
                  </p>
                </div>
                <Badge :variant="activity.type === 'loan' ? 'destructive' : 'secondary'">
                  {{ formatCurrency(activity.amount) }}
                </Badge>
              </div>
            </div>
          </ScrollArea>
        </CardContent>
      </Card>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
      <Card class="md:col-span-2">
        <CardHeader>
          <CardTitle>Financial Trends</CardTitle>
          <CardDescription>Monthly contribution and loan trends</CardDescription>
        </CardHeader>
        <CardContent>
          <!-- Placeholder for financial charts -->
          <div class="h-[300px] flex items-center justify-center text-muted-foreground">
            Financial Charts Coming Soon
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Quick Actions</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <Button class="w-full" variant="default">
            <WalletIcon class="mr-2 h-4 w-4" /> Generate Financial Report
          </Button>
          <Button class="w-full" variant="secondary">
            <ReceiptIcon class="mr-2 h-4 w-4" /> Manage Contributions
          </Button>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

<style scoped>

</style>
