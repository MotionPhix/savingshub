<script setup>
import {router, useForm} from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import FormField from '@/Components/Forms/FormField.vue'
import {Button} from "@/Components/ui/button/index.js";
import {computed, ref, watch} from "vue";
import {toast} from "vue-sonner";
import {Separator} from "@/Components/ui/separator";

const form = useForm({
  name: '',
  slug: '',
  description: '',
  mission_statement: '',
  contribution_frequency: 'monthly',
  contribution_amount: null,
  duration_months: 12,
  start_date: getWeekFromToday(),
  end_date: null,
  status: 'active',
  is_public: false,
  allow_member_invites: true,
  loan_interest_type: 'fixed',
  base_interest_rate: 5 / 100,
  interest_tiers: [],
  max_loan_amount: null,
  loan_duration_months: 1,
  require_group_approval: true,
  notification_preferences: {},
  settings: {},
})

function getWeekFromToday() {
  const today = new Date();
  const weekFromToday = new Date(today.setDate(today.getDate() + 8));
  return weekFromToday.toISOString().split('T')[0]; // Format as YYYY-MM-DD
}

function calculateEndDate() {
  if (form.start_date && form.duration_months) {
    const startDate = new Date(form.start_date);
    const endDate = new Date(startDate.getFullYear(), startDate.getMonth() + form.duration_months, startDate.getDate());
    form.end_date = endDate.toISOString().split('T')[0]; // Format as YYYY-MM-DD
  }
}

const minLoanDuration = computed(() => {
  switch (form.contribution_frequency) {
    case 'weekly':
      return 1;

    case 'monthly':
      return 6;

    default:
      return 12;
  }
})

const submitForm = () => {
  form.post(route('groups.store'), {
    preserveScroll: true,
    onSuccess: () => {
      // Reset form after successful submission
      form.reset()

      // Show success notification
      toast.success('Group created successfully!')

      // Redirect to the newly created group's dashboard
      router.visit(route('groups.dashboard', response.group.id))
    },
    onError: (errors) => {

      Object.entries(errors).forEach(([field, error]) => {
        toast.error(`${field.charAt(0).toUpperCase() + field.slice(1)}: ${error}`)
      })

      // Handle validation errors
      if (errors.start_date) {
        toast.error('Start date needs fixing', {
          description: errors.start_date
        })
      } else {
        if (errors.message) toast.error(errors.message)
      }
    }
  })

// Computed properties for dynamic form validation
  const isFormValid = computed(() => {
    return form.name.trim().length > 0 &&
      form.contribution_amount > 0 &&
      form.duration_months > 0
  })

// Optional: Interest Tiers for Tiered Interest Type
  const interestTiers = ref([
    {min_amount: 0, max_amount: 1000, rate: 5}
  ])

  const addInterestTier = () => {
    interestTiers.value.push({
      min_amount: 0,
      max_amount: 0,
      rate: 5
    })
  }

  const removeInterestTier = (index) => {
    interestTiers.value.splice(index, 1)
  }
}

watch(() => form.start_date, calculateEndDate);
watch(() => form.duration_months, calculateEndDate);
</script>

<template>
  <AppLayout>
    <div class="container mx-auto px-4 py-8">
      <div class="max-w-2xl mx-auto bg-white dark:bg-gray-800 shadow-lg rounded-lg p-8">
        <h1 class="text-2xl font-bold mb-6 text-center">
          Create a New Group
        </h1>

        <form @submit.prevent="submitForm">
          <!-- Basic Group Information -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <FormField
              label="Group Name"
              v-model="form.name"
              type="text"
              :error="form.errors.name"
              placeholder="Enter group name"
              required
            />

            <FormField
              label="Mission Statement"
              v-model="form.mission_statement"
              :error="form.errors.mission_statement"
              placeholder="Optional group mission"
            />
          </div>

          <FormField
            type="textarea"
            label="Description" class="mt-4"
            v-model="form.description"
            :error="form.errors.description"
            placeholder="Describe your group's purpose"
          />

          <!-- Contribution Settings -->
          <div class="mt-6">
            <h2 class="text-lg font-semibold mb-4">
              Contribution Settings
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <FormField
                type="select"
                label="Contribution Frequency"
                v-model="form.contribution_frequency"
                :error="form.errors.contribution_frequency"
                :options="[
                  {
                    label: 'Weekly',
                    value: 'weekly'
                  },
                  {
                    label: 'Monthly',
                    value: 'monthly'
                  },
                  {
                    label: 'Quarterly',
                    value: 'quarterly'
                  }
                ]"
              />

              <FormField
                label="Contribution Amount"
                v-model.number="form.contribution_amount"
                :error="form.errors.contribution_amount"
                format="currency"
                type="number"
                class="input"
                placeholder="Amount"
                :min="10000"
                :max="5000000"
              />

              <FormField
                label="Group Duration (Months)"
                v-model.number="form.duration_months"
                :error="form.errors.duration_months"
                type="number"
                class="input"
                placeholder="Duration"
                :min="minLoanDuration"
              />
            </div>
          </div>

          <!-- Loan Settings -->
          <div class="mt-6">
            <h2 class="text-lg font-semibold mb-4">Loan Settings</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <FormField
                type="select"
                label="Loan Interest Type"
                :error="form.errors.loan_interest_type"
                v-model="form.loan_interest_type"
                :options="[
                  { value: 'fixed', label: 'Fixed Rate' },
                  { value: 'variable', label: 'Variable Rate' },
                  { value: 'tiered', label: 'Tiered Rate' }
                ]"
              />

              <FormField
                label="Base Interest Rate (%)"
                v-model.number="form.base_interest_rate"
                :error="form.errors.base_interest_rate"
                format="percent"
                type="number"
                placeholder="Interest Rate"
                :step="0.01"
                :min="0"
              />

              <FormField
                label="Max Loan Amount"
                v-model.number="form.max_loan_amount"
                :error="form.errors.max_loan_amount"
                format="currency"
                type="number"
                class="input"
                placeholder="Maximum Loan Amount"
                :min="0"
              />

              <FormField
                label="Loan Duration (Months)"
                v-model.number="form.loan_duration_months"
                :error="form.errors.loan_duration_months"
                type="number"
                class="input"
                placeholder="Loan Duration"
                :min="1"
              />
            </div>
          </div>

          <!-- Group Preferences -->
          <div class="mt-6">
            <h2 class="text-lg font-semibold mb-4">Group Preferences</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

              <FormField
                type="radio"
                orientation="horizontal"
                label="Group Visibility"
                :error="form.errors.allow_member_invites"
                :options="[
                  {
                    value: true,
                    label: 'Public'
                  },
                  {
                    value: false,
                    label: 'Private'
                  }
                ]"
                v-model="form.is_public"
              />

              <FormField
                type="checkbox"
                label="Member Invitations"
                v-model="form.allow_member_invites"
                placeholder="Allow Members to Invite"
              />

              <FormField
                type="checkbox"
                label="Loan Approval"
                :error="form.errors.require_group_approval"
                placeholder="Require Group Approval for Loans"
                v-model="form.require_group_approval"
              />
            </div>
          </div>

          <Separator class="mt-6"/>

          <!-- Submit Button -->
          <div class="mt-8 flex justify-end">
            <Button
              type="submit"
              :disabled="form.processing">
              {{ form.processing ? 'Creating...' : 'Create Group' }}
            </Button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
