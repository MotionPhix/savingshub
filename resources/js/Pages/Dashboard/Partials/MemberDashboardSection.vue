<script setup>
import {
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  CardFooter
} from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  personalContributions: {
    type: Array,
    default: () => []
  },
  loanStatus: {
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

const viewContributionDetails = (groupName) => {
  router.visit(route('contributions.group', { group: groupName }))
}

const viewLoanDetails = (loan) => {
  router.visit(route('loans.details', {
    group: loan.groupName,
    loan: loan.loanDetails.id
  }))
}
</script>

<template>
  <div class="space-y-6">
    <Card>
      <CardHeader>
        <CardTitle>Personal Contributions</CardTitle>
        <CardDescription>Your savings across groups</CardDescription>
      </CardHeader>

      <CardContent>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
          <Card
            v-for="contribution in personalContributions"
            :key="contribution.groupName"
            class="hover:shadow-lg transition-shadow">
            <CardHeader>
              <CardTitle>{{ contribution.groupName }}</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-2">
                <div class="flex justify-between items-center">
                  <span>Total Contributed</span>
                  <span class="font-semibold text-green-600">
                    {{ formatCurrency(contribution.totalContributed) }}
                  </span>
                </div>
                <div class="flex justify-between items-center">
                  <span>Contribution Count</span>
                  <Badge variant="secondary">
                    {{ contribution.contributionCount }}
                  </Badge>
                </div>
              </div>
            </CardContent>

            <CardFooter>
              <Button
                variant="outline"
                class="w-full"
                @click="viewContributionDetails(contribution.groupName)">
                View Details
              </Button>
            </CardFooter>
          </Card>
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardHeader>
        <CardTitle>Loan Status</CardTitle>
        <CardDescription>Your active and past loans</CardDescription>
      </CardHeader>
      <CardContent>
        <div
          v-for="loan in loanStatus"
          :key="loan.groupName"
          class="flex justify-between items-center p-4 border-b last:border-b-0"
        >
          <div>
            <p class="font-medium">{{ loan.groupName }}</p>
            <p
              :class="[
                'text-sm',
                loan.hasActiveLoan
                  ? 'text-destructive'
                  : 'text-muted-foreground'
              ]"
            >
              {{ loan.hasActiveLoan ? 'Active Loan' : 'No Active Loan' }}
            </p>
          </div>
          <div class="text-right">
            <p
              v-if="loan.hasActiveLoan"
              class="font-semibold text-destructive"
            >
              {{ formatCurrency(loan.loanDetails.amount) }}
            </p>
            <Button
              v-if="loan.hasActiveLoan"
              variant="outline"
              size="sm"
              @click="viewLoanDetails(loan)"
            >
              Loan Details
            </Button>
            <span v-else class="text-muted-foreground">No Active Loan</span>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
