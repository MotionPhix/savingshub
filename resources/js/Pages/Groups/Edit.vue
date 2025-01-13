<script setup>
import {computed, ref} from 'vue'
import {useForm, router} from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  Card, CardContent, CardHeader, CardTitle
} from '@/Components/ui/card'
import {Button} from '@/Components/ui/button'
import {Input} from '@/Components/ui/input'
import {Textarea} from '@/Components/ui/textarea'
import {Checkbox} from '@/Components/ui/checkbox'
import {Label} from '@/Components/ui/label'
import {
  Select,
  SelectTrigger,
  SelectValue,
  SelectContent,
  SelectItem
} from '@/Components/ui/select'
import FormField from '@/Components/Forms/FormField.vue'

const props = defineProps({
  group: {
    type: Object,
    required: true
  }
})

const processing = ref(false)

// Create form with initial group data
const form = useForm({
  name: props.group.name,
  slug: props.group.slug,
  description: props.group.description || '',
  mission_statement: props.group.mission_statement || '',
  contribution_frequency: props.group.contribution_frequency,
  contribution_amount: props.group.contribution_amount,
  duration_months: props.group.duration_months,
  start_date: props.group.start_date
    ? new Date(props.group.start_date).toISOString().split('T')[0]
    : null,
  end_date: props.group.end_date
    ? new Date(props.group.end_date).toISOString().split('T')[0]
    : null,
  status: props.group.status,
  is_public: props.group.is_public,
  allow_member_invites: props.group.allow_member_invites,
  loan_interest_type: props.group.loan_interest_type,
  base_interest_rate: props.group.base_interest_rate,
  max_loan_amount: props.group.max_loan_amount,
  loan_duration_months: props.group.loan_duration_months,
  require_group_approval: props.group.require_group_approval,
})

// Validation rules
const validateForm = () => {
  const errors = {}

  // Basic validations
  if (!form.name.trim()) {
    errors.name = 'Group name is required'
  }

  if (form.contribution_amount <= 0) {
    errors.contribution_amount = 'Contribution amount must be positive'
  }

  if (form.duration_months <= 0) {
    errors.duration_months = 'Group duration must be positive'
  }

  // Date validations
  if (form.start_date && form.end_date) {
    const startDate = new Date(form.start_date)
    const endDate = new Date(form.end_date)

    if (endDate <= startDate) {
      errors.end_date = 'End date must be after start date'
    }
  }

  return errors
}

const updateGroup = () => {
  // Validate form
  const validationErrors = validateForm()

  if (Object.keys(validationErrors).length > 0) {
    // Handle validation errors
    Object.entries(validationErrors).forEach(([field, message]) => {
      // You might want to use a toast or form error handling
      toast.error(`${field}: ${message}`)
    })
    return
  }

  // Set processing state
  processing.value = true

  // Submit form
  form.put(route('groups.update', props.group.id), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Group updated successfully')
      processing.value = false
    },
    onError: (errors) => {
      toast.error('Failed to update group')
      processing.value = false
    },
    onFinish: () => {
      processing.value = false
    }
  })
}

// Additional computed properties
const canEditGroup = computed(() => {
  // Check if current user has permission to edit the group
  return props.group.creator.id === currentUser.value.id ||
    currentUser.value.hasRole('admin')
})

// Optional: Group statistics
const groupStatistics = computed(() => ({
  totalMembers: props.group.members_count || 0,
  pendingContributions: props.group.pending_contributions_count || 0,
  pendingLoans: props.group.pending_loan_requests_count || 0,
  pendingInvitations: props.group.pending_invitations_count || 0
}))
</script>

<template>
  <AppLayout>
    <div class="container mx-auto px-4 py-8">
      <Card>
        <CardHeader>
          <CardTitle>Edit Group: {{ form.name }}</CardTitle>
        </CardHeader>

        <CardContent>
          <form @submit.prevent="updateGroup" class="space-y-6">
            <!-- Basic Group Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <FormField label="Group Name" required>
                <Input
                  v-model="form.name"
                  placeholder="Enter group name"
                  required
                />
              </FormField>

              <FormField label="Slug">
                <Input
                  v-model="form.slug"
                  placeholder="Group slug"
                  :disabled="true"
                />
              </FormField>
            </div>

            <FormField label="Description">
              <Textarea
                v-model="form.description"
                placeholder="Describe your group's purpose"
                rows="4"
              />
            </FormField>

            <FormField label="Mission Statement">
              <Input
                v-model="form.mission_statement"
                placeholder="Optional group mission statement"
              />
            </FormField>

            <!-- Contribution Settings -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <FormField label="Contribution Frequency">
                <Select v-model="form.contribution_frequency">
                  <SelectTrigger>
                    <SelectValue placeholder="Select frequency"/>
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="weekly">Weekly</SelectItem>
                    <SelectItem value="monthly">Monthly</SelectItem>
                    <SelectItem value="quarterly">Quarterly</SelectItem>
                  </SelectContent>
                </Select>
              </FormField>

              <FormField label="Contribution Amount">
                <Input
                  v-model="form.contribution_amount"
                  type="number"
                  placeholder="Contribution amount"
                />
              </FormField>

              <FormField label="Group Duration (Months)">
                <Input
                  v-model="form.duration_months"
                  type="number"
                  placeholder="Duration in months"
                />
              </FormField>
            </div>

            <!-- Loan Settings -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <FormField label="Loan Interest Type">
                <Select v-model="form.loan_interest_type">
                  <SelectTrigger>
                    <SelectValue placeholder="Select interest type"/>
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="fixed">Fixed Rate</SelectItem>
                    <SelectItem value="variable">Variable Rate</SelectItem>
                    <SelectItem value="tiered">Tiered Rate</SelectItem>
                  </SelectContent>
                </Select>
              </FormField>

              <FormField label="Base Interest Rate (%)">
                <Input
                  v-model="form.base_interest_rate"
                  type="number"
                  step="0.01"
                  placeholder="Base interest rate"
                />
              </FormField>

              <FormField label="Max Loan Amount">
                <Input
                  v-model="form.max_loan_amount"
                  type="number"
                  placeholder="Maximum loan amount"
                />
              </FormField>
            </div>

            <!-- Group Preferences -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <FormField label="Group Status">
                <Select v-model="form.status">
                  <SelectTrigger>
                    <SelectValue placeholder="Select status"/>
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="active">Active</SelectItem>
                    <SelectItem value="inactive">Inactive</SelectItem>
                    <SelectItem value="completed">Completed</SelectItem>
                  </SelectContent>
                </Select>
              </FormField>

              <FormField label="Group Visibility">
                <div class="flex items-center space-x-4">
                  <Checkbox
                    v-model:checked="form.is_public"
                    id="is_public"
                  />
                  <Label for="is_public">Public Group</Label>
                </div>
              </FormField>

              <FormField label="Member Invitations">
                <div class="flex items-center space-x-4">
                  <Checkbox
                    v-model:checked="form.allow_member_invites"
                    id="allow_member_invites"
                  />
                  <Label for="allow_member_invites">Allow Member Invites</Label>
                </div>
              </FormField>
            </div>

            <!-- Loan Approval Preferences -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <FormField label="Loan Approval">
                <div class="flex items-center space-x-4">
                  <Checkbox
                    v-model:checked="form.require_group_approval"
                    id="require_group_approval"
                  />
                  <Label for="require_group_approval">Require Group Approval for Loans</Label>
                </div>
              </FormField>
            </div>

            <!-- Date Settings -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <FormField label="Start Date">
                <Input
                  v-model="form.start_date"
                  type="date"
                />
              </FormField>

              <FormField label="End Date">
                <Input
                  v-model="form.end_date"
                  type="date"
                />
              </FormField>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end space-x-4">
              <Button
                type="button"
                variant="outline"
                @click="router.get(route('groups.index'))"
              >
                Cancel
              </Button>
              <Button
                type="submit"
                :disabled="processing">
                {{ processing ? 'Updating...' : 'Update Group' }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

<style scoped>
.group-edit-form {
  @apply space-y-6 max-w-4xl mx-auto;
}

.form-section {
  @apply bg-white dark:bg-gray-800 rounded-lg shadow-md p-6;
}

.form-section-title {
  @apply text-lg font-semibold mb-4 text-gray-700 dark:text-gray-300;
}
</style>
