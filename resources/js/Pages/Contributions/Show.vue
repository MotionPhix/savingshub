<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
  CreditCardIcon,
  CalendarIcon,
  WalletIcon,
  CheckCircleIcon,
  EditIcon,
  TrashIcon,
  MessageCircleIcon,
  AlertTriangleIcon,
} from 'lucide-vue-next'

import AppLayout from "@/Layouts/AppLayout.vue"
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle
} from "@/Components/ui/card"
import { Button } from "@/Components/ui/button"
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
  DialogClose
} from "@/Components/ui/dialog"
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage
} from "@/Components/ui/form"
import { Input } from "@/Components/ui/input"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from "@/Components/ui/select"
import { Badge } from "@/Components/ui/badge"
import { toast } from "vue-sonner"
import { formatCurrency } from "@/lib/formatters"

// Enhanced Props
const props = defineProps({
  contribution: {
    type: Object,
    required: true
  },
  group: {
    type: Object,
    required: true
  },
  currentUserRole: {
    type: String,
    default: 'member'
  },
  isAdminOrTreasurer: {
    type: Boolean,
    default: false
  },
  memberContributionStatus: {
    type: Object,
    default: () => ({})
  }
})

// State Management
const isVerifyDialogOpen = ref(false)
const isDeleteDialogOpen = ref(false)
const isQueryDialogOpen = ref(false)
const isStatusUpdateDialogOpen = ref(false)

// Passwords and Verification
const adminPassword = ref('')
const treasurerPassword = ref('')
const secondApproverPassword = ref('')

// Contribution Query State
const queryTitle = ref('')
const queryDescription = ref('')

// Contribution Status Update
const newStatus = ref(props.contribution.status)
const statusUpdateNotes = ref('')

// Computed Properties
const contributionBalanceDetails = computed(() => {
  if (!props.memberContributionStatus) return null

  return {
    totalRequired: formatCurrency(props.group.contribution_amount),
    totalPaid: formatCurrency(props.memberContributionStatus.total_paid),
    remainingBalance: formatCurrency(
      Math.max(0, props.group.contribution_amount - props.memberContributionStatus.total_paid)
    ),
    isFullyPaid: props.memberContributionStatus.total_paid >= props.group.contribution_amount
  }
})

// Advanced Verification Method
function verifyContribution() {
  // Validate two-factor verification based on group settings
  const verificationRequirements = {
    'admin_treasurer': {
      primaryRole: 'admin',
      secondaryRole: 'treasurer'
    },
    'admin_secretary': {
      primaryRole: 'admin',
      secondaryRole: 'secretary'
    }
  }

  const requiredVerification = verificationRequirements[props.group.verification_method]

  if (!requiredVerification) {
    toast.error('Invalid verification method configured')
    return
  }

  router.post(route('contributions.verify', props.contribution.uuid), {
    status: newStatus.value,
    notes: statusUpdateNotes.value,
    primary_password: adminPassword.value,
    secondary_password: secondApproverPassword.value,
    primary_role: requiredVerification.primaryRole,
    secondary_role: requiredVerification.secondaryRole
  }, {
    onSuccess: () => {
      toast.success('Contribution verified successfully')
      isVerifyDialogOpen.value = false
    },
    onError: (errors) => {
      toast.error(errors.message || 'Verification failed')
    }
  })
}

// Submit Contribution Query
function submitContributionQuery() {
  router.post(route('contributions.query', props.contribution.uuid), {
    title: queryTitle.value,
    description: queryDescription.value
  }, {
    onSuccess: () => {
      toast.success('Query submitted successfully')
      isQueryDialogOpen.value = false
      queryTitle.value = ''
      queryDescription.value = ''
    }
  })
}

// Contribution Deletion with Two-Factor Verification
function deleteContribution() {
  const verificationRequirements = {
    'admin_treasurer': {
      primaryRole: 'admin',
      secondaryRole: 'treasurer'
    },
    'admin_secretary': {
      primaryRole: 'admin',
      secondaryRole: 'secretary'
    }
  }

  const requiredVerification = verificationRequirements[props.group.verification_method]

  router.delete(route('contributions.destroy', props.contribution.uuid), {
    primary_password: adminPassword.value,
    secondary_password: secondApproverPassword.value,
    primary_role: requiredVerification.primaryRole,
    secondary_role: requiredVerification.secondaryRole
  }, {
    onSuccess: () => {
      toast.success('Contribution deleted successfully')
      router.visit(route('contributions.index'))
    },
    onError: (errors) => {
      toast.error(errors.message || 'Deletion failed')
    }
  })
}

// Computed Properties
const contributionStatusVariants = computed(() => ({
  pending: 'warning',
  partial: 'secondary',
  paid: 'success',
  overdue: 'destructive'
}))

const contributionDetails = computed(() => [
  {
    label: 'Contribution Amount',
    value: formatCurrency(props.contribution.amount),
    icon: WalletIcon
  },
  {
    label: 'Contribution Date',
    value: new Date(props.contribution.contribution_date).toLocaleDateString(),
    icon: CalendarIcon
  },
  {
    label: 'Payment Method',
    value: props.contribution.payment_method ?? 'Not Specified',
    icon: CreditCardIcon
  }
])

// Group Contribution Insights
const groupContributionInsights = computed(() => {
  const currentDate = new Date()
  const groupStartDate = new Date(props.group.start_date)
  const groupEndDate = new Date(props.group.end_date)

  const totalMonths = Math.ceil(
    (groupEndDate.getTime() - groupStartDate.getTime()) /
    (1000 * 3600 * 24 * 30)
  )

  const currentMonthInGroup = Math.ceil(
    (currentDate.getTime() - groupStartDate.getTime()) /
    (1000 * 3600 * 24 * 30)
  )

  const remainingMonths = totalMonths - currentMonthInGroup

  return {
    totalMonths,
    currentMonthInGroup,
    remainingMonths,
    nextContributionDate: calculateNextContributionDate()
  }
})

// Methods
function calculateNextContributionDate() {
  // Logic to calculate next contribution date based on group's contribution frequency
  const lastContributionDate = new Date(props.contribution.contribution_date)
  const nextDate = new Date(lastContributionDate)

  switch(props.group.contribution_frequency) {
    case 'monthly':
      nextDate.setMonth(lastContributionDate.getMonth() + 1)
      break
    case 'weekly':
      nextDate.setDate(lastContributionDate.getDate() + 7)
      break
    case 'quarterly':
      nextDate.setMonth(lastContributionDate.getMonth() + 3)
      break
    default:
      return null
  }

  return nextDate
}

/*function verifyContribution() {
  // Validate passwords if group requires approval
  if (props.group.require_group_approval && !treasurerPassword.value) {
    toast.error('Treasurer password is required for verification')
    return
  }

  router.post(route('contributions.verify', props.contribution.uuid), {
    status: verificationStatus.value,
    notes: verificationNotes.value,
    admin_password: adminPassword.value,
    treasurer_password: treasurerPassword.value
  }, {
    onSuccess: () => {
      toast.success('Contribution verified successfully')
      isVerifyDialogOpen.value = false
    },
    onError: (errors) => {
      toast.error(errors.message || 'Verification failed')
    }
  })
}

function deleteContribution() {
  // Similar password validation as verify
  router.delete(route('contributions.destroy', props.contribution.uuid), {
    admin_password: adminPassword.value,
    treasurer_password: props.group.require_group_approval ? treasurerPassword.value : null,
    onSuccess: () => {
      toast.success('Contribution deleted successfully')
      router.visit(route('contributions.index'))
    },
    onError: (errors) => {
      toast.error(errors.message || 'Deletion failed')
    }
  })
}*/
</script>

<template>
  <AppLayout>
    <div class="container mx-auto p-4 space-y-6">
      <!-- Contribution Header -->
      <Card>
        <CardHeader>
          <div class="flex justify-between items-center">
            <div>
              <CardTitle>Contribution Details</CardTitle>
              <CardDescription>
                Contribution made on {{ new Date(contribution.contribution_date).toLocaleDateString() }}
              </CardDescription>
            </div>

            <Badge
              :variant="contributionStatusVariants[contribution.status]"
              class="capitalize"
            >
              {{ contribution.status }}
            </Badge>
          </div>
        </CardHeader>

        <CardContent>
          <div class="grid md:grid-cols-3 gap-4">
            <div
              v-for="(detail, index) in contributionDetails"
              :key="index"
              class="bg-muted/50 p-4 rounded-lg"
            >
              <div class="flex items-center space-x-2">
                <component
                  :is="detail.icon"
                  class="h-5 w-5 text-muted-foreground"
                />
                <span class="text-sm text-muted-foreground">
                  {{ detail.label }}
                </span>
              </div>
              <div class="text-lg font-bold mt-2">
                {{ detail.value }}
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Group Contribution Insights for Members -->
      <Card v-if="!isAdminOrTreasurer">
        <CardHeader>
          <CardTitle>Group Contribution Insights</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid md:grid-cols-2 gap-4">
            <div class="bg-muted/50 p-4 rounded-lg">
              <h3 class="text-sm text-muted-foreground">Months in Group</h3>
              <div class="text-lg font-bold">
                {{ groupContributionInsights.currentMonthInGroup }} / {{ groupContributionInsights.totalMonths }}
              </div>
            </div>

            <div class="bg-muted/50 p-4 rounded-lg">
              <h3 class="text-sm text-muted-foreground">Remaining Months</h3>
              <div class="text-lg font-bold">
                {{ groupContributionInsights.remainingMonths }}
              </div>
            </div>

            <div class="bg-muted/50 p-4 rounded-lg">
              <h3 class="text-sm text-muted-foreground">Next Contribution Date</h3>
              <div class="text-lg font-bold">
                {{
                  groupContributionInsights.nextContributionDate
                    ? new Date(groupContributionInsights.nextContributionDate).toLocaleDateString()
                    : 'Not Available'
                }}
              </div>
            </div>

            <div class="bg-muted/50 p-4 rounded-lg">
              <h3 class="text-sm text-muted-foreground">Contribution Status</h3>
              <Badge
                :variant="contributionStatusVariants[contribution.status]"
                class="capitalize"
              >
                {{ contribution.status }}
              </Badge>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Admin/Treasurer Actions -->
      <Card v-if="isAdminOrTreasurer">
        <CardHeader>
          <CardTitle>Contribution Management</CardTitle>
          <CardDescription>
            Administrative actions for this contribution
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid md:grid-cols-3 gap-4">
            <!-- Verification Action -->
            <Button
              variant="outline"
              @click="isVerifyDialogOpen = true"
              :disabled="contribution.is_verified"
            >
              <CheckCircleIcon class="mr-2 h-4 w-4" />
              {{ contribution.is_verified ? 'Verified' : 'Verify Contribution' }}
            </Button>

            <!-- Status Update Action -->
            <Button variant="outline" @click="openStatusUpdateDialog">
              <EditIcon class="mr-2 h-4 w-4" />
              Update Status
            </Button>

            <!-- Delete Contribution Action -->
            <Button variant="destructive" @click="isDeleteDialogOpen = true">
              <TrashIcon class="mr-2 h-4 w-4" />
              Delete Contribution
            </Button>
          </div>
        </CardContent>
      </Card>

      <!-- Verification Dialog -->
      <Dialog v-model:open="isVerifyDialogOpen">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Verify Contribution</DialogTitle>
            <DialogDescription>
              Confirm and verify this contribution
            </DialogDescription>
          </DialogHeader>

          <form @submit.prevent="verifyContribution" class="space-y-4">
            <FormField>
              <FormLabel>Admin Password</FormLabel>
              <FormControl>
                <Input
                  type="password"
                  v-model="adminPassword"
                  required
                  placeholder="Enter admin password"
                />
              </FormControl>
            </FormField>

            <FormField v-if="group.require_group_approval">
              <FormLabel>Treasurer Password</FormLabel>
              <FormControl>
                <Input
                  type="password"
                  v-model="treasurerPassword"
                  required
                  placeholder="Enter treasurer password"
                />
              </FormControl>
            </FormField>

            <FormField>
              <FormLabel>Verification Status</FormLabel>
              <Select v-model="verificationStatus">
                <SelectTrigger>
                  <SelectValue placeholder="Select status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="paid">Paid</SelectItem>
                  <SelectItem value="pending">Pending</SelectItem>
                  <SelectItem value="partial">Partial</SelectItem>
                </SelectContent>
              </Select>
            </FormField>

            <FormField>
              <FormLabel>Verification Notes (Optional)</FormLabel>
              <FormControl>
                <Input
                  v-model="verificationNotes"
                  placeholder="Additional verification notes"
                />
              </FormControl>
            </FormField>

            <DialogFooter>
              <Button type="submit" variant="default">
                Verify Contribution
              </Button>
              <DialogClose as-child>
                <Button variant="outline">Cancel</Button>
              </DialogClose>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <!-- Delete Confirmation Dialog -->
      <Dialog v-model:open="isDeleteDialogOpen">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Delete Contribution</DialogTitle>
            <DialogDescription>
              Are you sure you want to delete this contribution? This action cannot be undone.
            </DialogDescription>
          </DialogHeader>

          <form @submit.prevent="deleteContribution" class="space-y-4">
            <FormField>
              <FormLabel>Admin Password</FormLabel>
              <FormControl>
                <Input
                  type="password"
                  v-model="adminPassword"
                  required
                  placeholder="Enter admin password"
                />
              </FormControl>
            </FormField>

            <FormField v-if="group.require_group_approval">
              <FormLabel>Treasurer Password</FormLabel>
              <FormControl>
                <Input
                  type="password"
                  v-model="treasurerPassword"
                  required
                  placeholder="Enter treasurer password"
                />
              </FormControl>
            </FormField>

            <DialogFooter>
              <Button type="submit" variant="destructive">
                Confirm Delete
              </Button>
              <DialogClose as-child>
                <Button variant="outline">Cancel</Button>
              </DialogClose>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <!-- Contribution Activity Log -->
      <Card>
        <CardHeader>
          <CardTitle>Contribution Activity</CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="contribution.metadata?.activity_log?.length">
            <div
              v-for="(log, index) in contribution.metadata.activity_log"
              :key="index"
              class="border-b last:border-b-0 py-2">
              <div class="flex justify-between items-center">
                <div>
                  <span class="font-medium">{{ log.action }}</span>
                  <span class="text-muted-foreground text-sm ml-2">
                    {{ new Date(log.timestamp).toLocaleString() }}
                  </span>
                </div>
                <Badge :variant="log.type === 'error' ? 'destructive' : 'default'">
                  {{ log.type }}
                </Badge>
              </div>
              <p class="text-sm text-muted-foreground mt-1">
                {{ log.description }}
              </p>
            </div>
          </div>
          <div v-else class="text-muted-foreground text-center py-4">
            No activity logged for this contribution
          </div>
        </CardContent>
      </Card>

      <!-- Member Contribution Balance Section -->
      <Card v-if="!isAdminOrTreasurer && contributionBalanceDetails">
        <CardHeader>
          <CardTitle>Contribution Balance</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid md:grid-cols-2 gap-4">
            <div class="bg-muted/50 p-4 rounded-lg">
              <h3 class="text-sm text-muted-foreground">Total Required</h3>
              <div class="text-lg font-bold">
                {{ contributionBalanceDetails.totalRequired }}
              </div>
            </div>
            <div class="bg-muted/50 p-4 rounded-lg">
              <h3 class="text-sm text-muted-foreground">Total Paid</h3>
              <div class="text-lg font-bold">
                {{ contributionBalanceDetails.totalPaid }}
              </div>
            </div>
            <div class="bg-muted/50 p-4 rounded-lg">
              <h3 class="text-sm text-muted-foreground">Remaining Balance</h3>
              <div
                :class="`text-lg font-bold ${contributionBalanceDetails.isFullyPaid ? 'text-green-600' : 'text-red-600'}`"
              >
                {{ contributionBalanceDetails.remainingBalance }}
              </div>
            </div>
            <div class="bg-muted/50 p-4 rounded-lg">
              <h3 class="text-sm text-muted-foreground">Payment Status</h3>
              <Badge
                :variant="contributionBalanceDetails.isFullyPaid ? 'success' : 'warning'"
              >
                {{ contributionBalanceDetails.isFullyPaid ? 'Fully Paid' : 'Partial Payment' }}
              </Badge>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Member Query Section -->
      <Card v-if="!isAdminOrTreasurer">
        <CardHeader>
          <CardTitle>Have a Question?</CardTitle>
          <CardDescription>
            If you have any issues or queries about this contribution
          </CardDescription>
        </CardHeader>
        <CardContent>
          <Button @click="isQueryDialogOpen = true" variant="outline">
            <MessageCircleIcon class="mr-2 h-4 w-4" />
            Submit a Query
          </Button>
        </CardContent>
      </Card>

      <!-- Query Dialog -->
      <Dialog v-model:open="isQueryDialogOpen">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Submit Contribution Query</DialogTitle>
            <DialogDescription>
              Describe your concern about this contribution
            </DialogDescription>
          </DialogHeader>
          <form @submit.prevent="submitContributionQuery" class="space-y-4">
            <FormField>
              <FormLabel>Query Title</FormLabel>
              <FormControl>
                <Input
                  v-model="queryTitle"
                  required
                  placeholder="Briefly describe your query"
                />
              </FormControl>
            </FormField>

            <FormField>
              <FormLabel>Detailed Description</FormLabel>
              <FormControl>
                <textarea
                  v-model="queryDescription"
                  rows="4"
                  class="w-full border rounded-md p-2"
                  placeholder="Provide more details about your concern"
                  required
                ></textarea>
              </FormControl>
            </FormField>

            <DialogFooter>
              <Button type="submit" variant="default">
                Submit Query
              </Button>
              <DialogClose as-child>
                <Button variant="outline">Cancel</Button>
              </DialogClose>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <!-- Two-Factor Verification Dialog -->
      <Dialog v-model:open="isVerifyDialogOpen">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Verify Contribution</DialogTitle>
            <DialogDescription>
              This contribution requires verification from two senior members
            </DialogDescription>
          </DialogHeader>

          <form @submit.prevent="verifyContribution" class="space-y-4">
            <div class="flex items-center space-x-2 bg-muted/50 p-3 rounded-lg">
              <AlertTriangleIcon class="h-5 w-5 text-yellow-500" />
              <span class="text-sm">
                Verification requires approval from
                {{ group.verification_method === 'admin_treasurer'
                ? 'Admin and Treasurer'
                : 'Admin and Secretary'
                }}
              </span>
            </div>

            <FormField>
              <FormLabel>Admin Password</FormLabel>
              <FormControl>
                <Input
                  type="password"
                  v-model="adminPassword"
                  required
                  placeholder="Enter admin password"
                />
              </FormControl>
            </FormField>

            <FormField>
              <FormLabel>
                {{
                  group.verification_method === 'admin_treasurer'
                    ? 'Treasurer Password'
                    : 'Secretary Password'
                }}
              </FormLabel>
              <FormControl>
                <Input
                  type="password"
                  v-model="secondApproverPassword"
                  required
                  placeholder="Enter second approver's password"
                />
              </FormControl>
            </FormField>

            <FormField>
              <FormLabel>Verification Status</FormLabel>
              <Select v-model="newStatus">
                <SelectTrigger>
                  <SelectValue placeholder="Select new status" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="paid">Paid</SelectItem>
                  <SelectItem value="pending">Pending</SelectItem>
                  <SelectItem value="partial">Partial</SelectItem>
                  <SelectItem value="overdue">Overdue</SelectItem>
                </SelectContent>
              </Select>
            </FormField>

            <FormField>
              <FormLabel>Verification Notes</FormLabel>
              <FormControl>
                <Input
                  v-model="statusUpdateNotes"
                  placeholder="Additional verification notes"
                />
              </FormControl>
            </FormField>

            <DialogFooter>
              <Button type="submit" variant="default">
                Verify Contribution
              </Button>
              <DialogClose as-child>
                <Button variant="outline">Cancel</Button>
              </DialogClose>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <!-- Comprehensive Contribution Queries Section -->
      <Card>
        <CardHeader>
          <CardTitle>Contribution Queries</CardTitle>
          <CardDescription>
            Queries and discussions related to this contribution
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div
            v-if="contribution.queries && contribution.queries.length"
            class="space-y-4"
          >
            <div
              v-for="query in contribution.queries"
              :key="query.id"
              class="border rounded-lg p-4"
            >
              <div class="flex justify-between items-center mb-2">
                <h3 class="font-semibold">{{ query.title }}</h3>
                <Badge :variant="query.status === 'resolved' ? 'success' : 'warning'">
                  {{ query.status }}
                </Badge>
              </div>
              <p class="text-muted-foreground">{{ query.description }}</p>
              <div class="text-xs text-muted-foreground mt-2">
                Submitted on {{ new Date(query.created_at).toLocaleString() }}
                {{ query.response ? ' - Responded' : '' }}
              </div>

              <!-- Query Response (if exists) -->
              <div
                v-if="query.response"
                class="mt-4 bg-muted/50 p-3 rounded-lg"
              >
                <h4 class="font-medium">Response:</h4>
                <p>{{ query.response }}</p>
                <div class="text-xs text-muted-foreground mt-1">
                  Responded by {{ query.responder_name }}
                  on {{ new Date(query.responded_at).toLocaleString() }}
                </div>
              </div>
            </div>
          </div>
          <div
            v-else
            class="text-center text-muted-foreground py-4"
          >
            No queries have been submitted for this contribution
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
