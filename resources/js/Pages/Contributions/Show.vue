<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import {
  CheckCircleIcon,
  XCircleIcon,
  EditIcon,
  TrashIcon,
  ClockIcon,
  AlertCircleIcon
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

// Props from Backend
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
  }
})

// State Management
const isVerifyDialogOpen = ref(false)
const isDeleteDialogOpen = ref(false)
const adminPassword = ref('')
treasurerPassword = ref('')
const verificationStatus = ref(props.contribution.status)
const verificationNotes = ref('')

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

function verifyContribution() {
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
}
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
              class="border-b last:border-b-0 py-2"
            >
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
    </div>
  </AppLayout>
</template>
