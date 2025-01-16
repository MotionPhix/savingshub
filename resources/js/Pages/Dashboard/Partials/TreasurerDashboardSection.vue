<template>
  <div class="space-y-6">
    <Card>
      <CardHeader>
        <CardTitle>Financial Overview</CardTitle>
        <CardDescription>Comprehensive financial insights</CardDescription>
      </CardHeader>
      <CardContent>
        <div class="grid md:grid-cols-3 gap-4">
          <Card
            v-for="overview in financialOverview"
            :key="overview.groupName"
          >
            <CardHeader>
              <CardTitle>{{ overview.groupName }}</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-2">
                <div class="flex justify-between">
                  <span>Total Contributions</span>
                  <span class="font-semibold text-green-600">
                    {{ formatCurrency(overview.totalContributions) }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span>Total Loans</span>
                  <span class="font-semibold text-destructive">
                    {{ formatCurrency(overview.totalLoans) }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span>Available Balance</span>
                  <span
                    :class="[
                      'font-semibold',
                      overview.availableBalance > 0
                        ? 'text-green-600'
                        : 'text-destructive'
                    ]"
                  >
                    {{ formatCurrency(overview.availableBalance) }}
                  </span>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardHeader>
        <CardTitle>Upcoming Financial Activities</CardTitle>
        <CardDescription>Pending financial transactions</CardDescription>
      </CardHeader>
      <CardContent>
        <div
          v-for="(activity, index) in upcomingActivities"
          :key="index"
          class="flex justify-between items-center p-4 border-b last:border-b-0"
        >
          <div>
            <p class="font-medium">
              {{ activity.type === 'pendingLoans' ? 'Pending Loan' : 'Upcoming Contribution' }}
            </p>
          </div>
          <div class="text-right">
            <p
              :class="[
                'font-semibold',
                activity.type === 'pendingLoans'
                  ? 'text-destructive'
                  : 'text-green-600'
              ]"
            >
              {{ formatCurrency(activity.amount) }}
            </p>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

<script setup>
import {
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent
} from '@/Components/ui/card'

const props = defineProps({
  financialOverview: {
    type: Array,
    default: () => []
  },
  upcomingActivities: {
    type: Array,
    default: () => []
  }
})

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'MWK'
  }).format(amount)
}
</script>
