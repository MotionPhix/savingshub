<script setup lang="ts">
import {router, useForm} from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import FormField from '@/Components/Forms/FormField.vue'
import {Button} from "@/Components/ui/button/index.js";
import {computed, ref, watch} from "vue";
import {toast} from "vue-sonner";
import {Separator} from "@/Components/ui/separator";
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover'
import { Calendar } from '@/Components/ui/v-calendar'
import { format } from 'date-fns'
import { Calendar as CalendarIcon } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import {truncateText} from "@/lib/formatters";
import InputError from "@/Components/InputError.vue";

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
  loan_interest_type: 'tiered',
  base_interest_rate: 5 / 100,
  interest_tiers: [],
  max_loan_amount: null,
  loan_duration_months: 1,
  require_group_approval: true,
  notification_preferences: {},
  settings: {},
})

const interestTiers = ref([
  { min_amount: 0, max_amount: 10000, rate: 0.05 }
])

function getWeekFromToday() {
  const today = new Date();
  const weekFromToday = new Date(today.setDate(today.getDate() + 7));
  return weekFromToday.toISOString().split('T')[0]; // Format as YYYY-MM-DD
}

function calculateEndDate() {
  if (form.start_date && form.duration_months) {
    const startDate = new Date(form.start_date);
    const endDate = new Date(startDate.getFullYear(), startDate.getMonth() + form.duration_months, startDate.getDate() - 1);
    form.end_date = endDate.toISOString().split('T')[0]; // Format as YYYY-MM-DD
  }
}

const formattedStartDate = computed(() => {
  return form.start_date
    ? format(new Date(form.start_date), 'PPPP')
    : 'Pick a date'
})

const formattedEndDate = computed(() => {
  return form.end_date
    ? format(new Date(form.end_date), 'PPPP')
    : 'Based on start date and duration'
})

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

// Update the addInterestTier function
const addInterestTier = () => {
  const lastTier = interestTiers.value[interestTiers.value.length - 1]

  interestTiers.value.push({
    min_amount: lastTier.max_amount + 1,
    max_amount: lastTier.max_amount * 2,
    rate: lastTier.rate + 1
  })
}

// Update the removeInterestTier function
const removeInterestTier = (index) => {
  if (interestTiers.value.length > 1) {
    interestTiers.value.splice(index, 1)
  }
}

// Watch for changes in loan interest type
watch(() => form.loan_interest_type, (newType) => {
  if (newType === 'tiered') {
    // Ensure interest tiers are populated when switching to tiered
    if (interestTiers.value.length === 0) {
      interestTiers.value = [
        { min_amount: 0, max_amount: 10000, rate: 5 }
      ]
    }
    // Update form with current tiers
    form.interest_tiers = interestTiers.value
  } else {
    // Clear tiers for non-tiered types
    interestTiers.value = []
    form.interest_tiers = []
  }
})

// Watch for changes in interest tiers
watch(interestTiers, (newTiers) => {
  if (form.loan_interest_type === 'tiered') {
    form.interest_tiers = newTiers
  }
}, { deep: true })

watch(() => form.start_date, calculateEndDate);
watch(() => form.duration_months, calculateEndDate);
</script>

<template>
  <AppLayout>
    <div class="mx-auto sm:px-4 py-8">
      <div class="max-w-2xl mx-auto bg-white dark:bg-gray-800 shadow rounded-lg p-8">
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

          <div
            class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Start Date
              </label>
              <Popover>
                <PopoverTrigger as-child>
                  <Button
                    variant="outline"
                    :class="cn(
                      'w-full min-h-10 justify-start text-left font-normal',
                      !form.start_date && 'text-muted-foreground'
                    )">
                    <CalendarIcon class="mr-2 h-4 w-4" />
                    {{ formattedStartDate }}
                  </Button>
                </PopoverTrigger>
                <PopoverContent class="w-auto p-0">
                  <Calendar
                    v-model="form.start_date"
                    mode="single"
                    initial-focus
                    @update:model-value="calculateEndDate"
                  />
                </PopoverContent>
              </Popover>

              <InputError class="mt-1" :message="form.errors.start_date" />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                End Date
              </label>
              <Popover>
                <PopoverTrigger as-child>
                  <Button
                    variant="outline"
                    :class="cn(
                      'w-full justify-start min-h-10 text-left font-normal cursor-not-allowed',
                      'text-muted-foreground'
                    )"
                    disabled>
                    <CalendarIcon class="mr-2 h-4 w-4" />
                    {{ truncateText(formattedEndDate) }}
                  </Button>
                </PopoverTrigger>
              </Popover>

              <p class="mt-1 text-xs text-gray-500">
                Automatically calculated
              </p>
            </div>

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

          <div
            v-if="form.loan_interest_type === 'tiered'" class="mt-6">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-lg font-semibold">Interest Tiers</h2>
              <Button
                type="button"
                variant="outline"
                size="sm"
                @click="addInterestTier">
                Add Tier
              </Button>
            </div>

            <div class="space-y-4">
              <div
                v-for="(tier, index) in interestTiers"
                :key="index"
                class="grid grid-cols-1 sm:grid-cols-5 gap-4 items-center">
                <div class="sm:col-span-2">
                  <FormField
                    type="number"
                    label="Min Amount"
                    v-model.number="tier.min_amount"
                    placeholder="Minimum"
                    :min="0"
                  />
                </div>

                <div class="sm:col-span-2">
                  <FormField
                    type="number"
                    label="Max Amount"
                    v-model.number="tier.max_amount"
                    placeholder="Maximum"
                    :min="tier.min_amount"
                  />
                </div>

                <div>
                  <FormField
                    type="number"
                    label="Interest Rate (%)"
                    v-model.number="tier.rate"
                    placeholder="Rate"
                    format="percent"
                    :step="0.01"
                    :min="0"
                    :max="100"
                  />
                </div>
              </div>
            </div>

            <div v-if="form.errors.interest_tiers" class="text-red-500 text-sm mt-2">
              {{ form.errors.interest_tiers }}
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
