<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  WalletIcon,
  CreditCardIcon,
  InfoIcon
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
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription
} from '@/Components/ui/dialog'
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage
} from '@/Components/ui/form'
import { Input } from '@/Components/ui/input'

const props = defineProps({
  user: Object,
  groups: Array,
  dashboardData: Object
})

// Utility Methods
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: props.user?.currency || 'MWK'
  }).format(amount)
}

// Action Methods
const makeContribution = () => {
  // Open contribution modal or navigate to contribution page
  router.visit(route('contributions.create'), {
    preserveState: true,
    preserveScroll: true
  })
}

const requestLoan = () => {
  // Open loan request modal or navigate to loan request page
  router.visit(route('loans.create'), {
    preserveState: true,
    preserveScroll: true
  })
}

const viewGroupDetails = () => {
  // If user is in only one group, navigate directly
  if (props.groups.length === 1) {
    router.visit(route('groups.show', props.groups[0].uuid))
  } else {
    // If multiple groups, show group selection dialog
    // Implement group selection logic
  }
}

// Computed Properties
const totalContributions = computed(() => {
  return props.dashboardData.personalContributions.reduce(
    (total, contribution) => total + contribution.totalContributed,
    0
  )
})

const hasActiveLoan = computed(() => {
  return props.dashboardData.loanStatus.some(
    loan => loan.hasActiveLoan
  )
})

// Optional: Reactive State for Modals
const showContributionModal = ref(false)
const showLoanRequestModal = ref(false)
</script>

<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold mb-6">My Contributions & Loans</h1>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <Card>
        <CardHeader>
          <CardTitle>Personal Contributions</CardTitle>
          <CardDescription>Your savings across groups</CardDescription>
        </CardHeader>
        <CardContent>
          <div
            v-for="contribution in dashboardData.personalContributions"
            :key="contribution.groupName"
            class="mb-4 p-4 bg-muted/50 rounded-lg"
          >
            <div class="flex justify-between items-center">
              <h3 class="text-lg font-semibold">
                {{ contribution.groupName }}
              </h3>
              <Badge variant="secondary">
                Total: {{ formatCurrency(contribution.totalContributed) }}
              </Badge>
            </div>
            <div class="mt-2 text-sm text-muted-foreground">
              Contribution Count: {{ contribution.contributionCount }}
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Loan Status</CardTitle>
          <CardDescription>Active and past loans</CardDescription>
        </CardHeader>
        <CardContent>
          <ScrollArea class="h-[300px]">
            <div
              v-for="loanStatus in dashboardData.loanStatus"
              :key="loanStatus.groupName"
              class="mb-3 p-3 bg-background border rounded-lg"
            >
              <div class="flex justify-between items-center">
                <div>
                  <h4 class="font-medium">{{ loanStatus.groupName }}</h4>
                  <p class="text-sm text-muted-foreground">
                    {{ loanStatus.hasActiveLoan ? 'Active Loan' : 'No Active Loans' }}
                  </p>
                </div>
                <div v-if="loanStatus.hasActiveLoan">
                  <Badge variant="destructive">
                    {{ formatCurrency(loanStatus.loanDetails.amount) }}
                  </Badge>
                </div>
              </div>

              <div
                v-if="loanStatus.hasActiveLoan"
                class="mt-2 text-sm space-y-1"
              >
                <p>
                  Remaining Balance:
                  <span class="font-semibold">
                    {{ formatCurrency(loanStatus.loanDetails.remainingBalance) }}
                  </span>
                </p>
                <p>
                  Status:
                  <Badge variant="outline">
                    {{ loanStatus.loanDetails.status }}
                  </Badge>
                </p>
              </div>
            </div>
          </ScrollArea>
        </CardContent>
      </Card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
      <Card class="lg:col-span-2">
        <CardHeader>
          <CardTitle>Savings Progress</CardTitle>
          <CardDescription>Your contribution trends</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="h-[300px] flex items-center justify-center text-muted-foreground">
            Savings Chart Coming Soon
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Quick Actions</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <Button class="w-full" @click="makeContribution">
            <WalletIcon class="mr-2 h-4 w-4" /> Make Contribution
          </Button>
          <Button class="w-full" variant="secondary" @click="requestLoan">
            <CreditCardIcon class="mr-2 h-4 w-4" /> Request Loan
          </Button>
          <Button class="w-full" variant="outline" @click="viewGroupDetails">
            <InfoIcon class="mr-2 h-4 w-4" /> View Group Details
          </Button>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

<style scoped>
/* Responsive adjustments */
@media (max-width: 640px) {
  .grid {
    grid-template-columns: 1fr !important;
  }
}
</style>
