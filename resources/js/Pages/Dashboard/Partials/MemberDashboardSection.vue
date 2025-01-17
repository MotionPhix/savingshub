<script setup>
import {
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  CardFooter
} from '@/Components/ui/card'
import {Button} from '@/Components/ui/button'
import {Badge} from '@/Components/ui/badge'
import {router} from '@inertiajs/vue3'
import {ref, computed} from 'vue'

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
  router.visit(route('contributions.group', {group: groupName}))
}

const viewLoanDetails = (loan) => {
  router.visit(route('loans.details', {
    group: loan.groupName,
    loan: loan.loanDetails.id
  }))
}

// Compute total contributions across all groups
const totalContributions = computed(() =>
  props.personalContributions.reduce((sum, contribution) =>
    sum + contribution.totalContributed, 0)
)

// Compute total active loans
const totalActiveLoans = computed(() =>
  props.loanStatus.filter(loan => loan.hasActiveLoan).length
)

// Optional: Add summary cards for mobile view
const renderSummarySections = computed(() => ({
  contributions: {
    total: totalContributions.value,
    count: props.personalContributions.length
  },
  loans: {
    activeLoans: totalActiveLoans.value,
    total: props.loanStatus.length
  }
}))

// Mobile-specific state for expandable sections
const expandedContributions = ref(false)
const expandedLoans = ref(false)
</script>

<template>
  <div class="space-y-6">
    <!-- Personal Contributions Section -->
    <Card>
      <CardHeader>
        <CardTitle>Personal Contributions</CardTitle>
        <CardDescription>Your savings across groups</CardDescription>
      </CardHeader>

      <CardContent>
        <!-- Desktop Grid View -->
        <div class="hidden md:grid md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="contribution in personalContributions"
            :key="contribution.groupName"
            class="border rounded-lg p-4 hover:shadow-lg transition-shadow"
          >
            <h3 class="text-lg font-semibold mb-4">
              {{ contribution.groupName }}
            </h3>

            <div class="space-y-2">
              <div class="flex flex-row justify-between items-center">
                <span>Total Contributed</span>
                <span class="font-semibold text-green-600 font-figures">
                  {{ formatCurrency(contribution.totalContributed) }}
                </span>
              </div>

              <div class="flex justify-between items-center">
                <span>Contribution Count</span>
                <Badge variant="secondary" class="font-figures">
                  {{ contribution.contributionCount }}
                </Badge>
              </div>
            </div>

            <Button
              variant="outline"
              class="w-full mt-4"
              @click="viewContributionDetails(contribution.groupName)">
              View Details
            </Button>
          </div>
        </div>

        <!-- Mobile Scrollable List -->
        <div class="md:hidden">
          <div
            class="space-y-4 max-h-[300px] overflow-y-auto"
            :class="{ 'max-h-full': expandedContributions }"
          >
            <Card
              v-for="contribution in personalContributions"
              :key="contribution.groupName"
              class="hover:bg-muted/50 transition-colors"
            >
              <CardHeader>
                <CardTitle class="text-base font-headings">
                  {{ contribution.groupName }}
                </CardTitle>
              </CardHeader>

              <CardContent>
                <div class="space-y-2">
                  <div class="flex flex-col">
                    <span>Total Contributed</span>
                    <span class="font-semibold text-green-600 font-figures">
                      {{ formatCurrency(contribution.totalContributed) }}
                    </span>
                  </div>

                  <div class="flex justify-between items-center">
                    <span>Contribution Count</span>
                    <Badge variant="secondary" class="font-figures">
                      {{ contribution.contributionCount }}
                    </Badge>
                  </div>
                </div>
              </CardContent>
              <CardFooter>
                <Button
                  variant="outline"
                  class="w-full"
                  @click="viewContributionDetails(contribution.groupName)"
                >
                  View Details
                </Button>
              </CardFooter>
            </Card>
          </div>

          <!-- Expand/Collapse Button for Mobile -->
          <Button
            v-if="personalContributions.length > 3"
            variant="ghost"
            class="w-full mt-4"
            @click="expandedContributions = !expandedContributions"
          >
            {{ expandedContributions ? 'Collapse' : 'Show More' }}
          </Button>
        </div>
      </CardContent>
    </Card>

    <!-- Loan Status Section -->
    <Card>
      <CardHeader>
        <CardTitle>Loan Status</CardTitle>
        <CardDescription>Your active and past loans</CardDescription>
      </CardHeader>
      <CardContent>
        <!-- Desktop List View -->
        <div class="hidden md:block">
          <div
            v-for="loan in loanStatus"
            :key="loan.groupName"
            class="flex justify-between items-center p-4 border-b last:border-b-0 hover:bg-muted/50 transition-colors"
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
        </div>

        <!-- Mobile Scrollable List -->
        <div class="md:hidden">
          <div
            class="space-y-4 max-h-[300px] overflow-y-auto"
            :class="{ 'max-h-full': expandedLoans }"
          >
            <Card
              v-for="loan in loanStatus"
              :key="loan.groupName"
              class="hover:bg-muted/50 transition-colors"
            >
              <CardHeader>
                <CardTitle class="text-base">
                  {{ loan.groupName }}
                </CardTitle>
                <CardDescription
                  :class="[
                    loan.hasActiveLoan
                      ? 'text-destructive'
                      : 'text-muted-foreground'
                  ]"
                >
                  {{ loan.hasActiveLoan ? 'Active Loan' : 'No Active Loan' }}
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div class="flex justify-between items-center">
                  <span>Loan Amount</span>
                  <p
                    v-if="loan.hasActiveLoan"
                    class="font-semibold text-destructive"
                  >
                    {{ formatCurrency(loan.loanDetails.amount) }}
                  </p>
                  <span v-else class="text-muted-foreground">No Active Loan</span>
                </div>
              </CardContent>
              <CardFooter v-if="loan.hasActiveLoan">
                <Button
                  variant="outline"
                  class="w-full"
                  @click="viewLoanDetails(loan)"
                >
                  Loan Details
                </Button>
              </CardFooter>
            </Card>
          </div>

          <!-- Expand/Collapse Button for Mobile -->
          <Button
            v-if="loanStatus.length > 3"
            variant="ghost"
            class="w-full mt-4"
            @click="expandedLoans = !expandedLoans"
          >
            {{ expandedLoans ? 'Collapse' : 'Show More' }}
          </Button>
        </div>
      </CardContent>
    </Card>

    <div class="md:hidden mb-4 space-y-4">
      <Card>
        <CardContent class="pt-6">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-sm text-muted-foreground">Total Contributions</p>
              <p class="text-2xl font-bold">
                {{ formatCurrency(renderSummarySections.contributions.total) }}
              </p>
            </div>
            <Badge variant="secondary">
              {{ renderSummarySections.contributions.count }} Groups
            </Badge>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="pt-6">
          <div class="flex justify-between items-center">
            <div>
              <p class="text-sm text-muted-foreground">Active Loans</p>
              <p class="text-2xl font-bold text-destructive">
                {{ renderSummarySections.loans.activeLoans }}
              </p>
            </div>
            <Badge variant="outline">
              {{ renderSummarySections.loans.total }} Total Loans
            </Badge>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

<style scoped>
/* Smooth transition for expandable sections */
.max-h-full {
  max-height: none;
}

/* Custom scrollbar for mobile views */
@media (max-width: 768px) {
  .overflow-y-auto {
    scrollbar-width: thin;
    scrollbar-color: theme('colors.muted.DEFAULT') transparent;
  }

  .overflow-y-auto::-webkit-scrollbar {
    width: 6px;
  }

  .overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
  }

  .overflow-y-auto::-webkit-scrollbar-thumb {
    background-color: theme('colors.muted.DEFAULT');
    border-radius: 20px;
  }
}
</style>
