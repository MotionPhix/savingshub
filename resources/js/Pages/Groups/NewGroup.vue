<script setup>
import {reactive} from 'vue'
import {useForm} from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import FormField from '@/Components/Forms/FormField.vue'

const form = useForm({
  name: '',
  slug: '',
  description: '',
  mission_statement: '',
  contribution_frequency: 'monthly',
  contribution_amount: null,
  duration_months: 12,
  start_date: new Date(),
  end_date: null,
  status: 'active',
  is_public: false,
  allow_member_invites: true,
  loan_interest_type: 'fixed',
  base_interest_rate: 5.00,
  interest_tiers: [],
  max_loan_amount: null,
  loan_duration_months: 6,
  require_group_approval: true,
  settings: {},
  notification_preferences: {}
})

const submitForm = () => {
  // Calculate end date based on start date and duration
  form.end_date = new Date(
    form.start_date.getFullYear(),
    form.start_date.getMonth() + form.duration_months,
    form.start_date.getDate()
  )

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
      // Handle validation errors
      toast.error('Please check the form for errors.')
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
            <FormField label="Group Name" required>
              <input
                v-model="form.name"
                type="text"
                class="input"
                placeholder="Enter group name"
                required
              />
            </FormField>

            <FormField label="Mission Statement">
              <input
                v-model="form.mission_statement"
                type="text"
                class="input"
                placeholder="Optional group mission"
              />
            </FormField>
          </div>

          <FormField label="Description" class="mt-4">
            <textarea
              v-model="form.description"
              class="input h-24"
              placeholder="Describe your group's purpose"
            ></textarea>
          </FormField>

          <!-- Contribution Settings -->
          <div class="mt-6">
            <h2 class="text-lg font-semibold mb-4">Contribution Settings</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <FormField label="Contribution Frequency">
                <select v-model="form.contribution_frequency" class="input">
                  <option value="weekly">Weekly</option>
                  <option value="monthly">Monthly</option>
                  <option value="quarterly">Quarterly</option>
                </select>
              </FormField>

              <FormField label="Contribution Amount">
                <input
                  v-model.number="form.contribution_amount"
                  type="number"
                  class="input"
                  placeholder="Amount"
                  min="0"
                />
              </FormField>

              <FormField label="Group Duration (Months)">
                <input
                  v-model.number="form.duration_months"
                  type="number"
                  class="input"
                  placeholder="Duration"
                  min="1"
                />
              </FormField>
            </div>
          </div>

          <!-- Loan Settings -->
          <div class="mt-6">
            <h2 class="text-lg font-semibold mb-4">Loan Settings</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <FormField label="Loan Interest Type">
                <select v-model="form.loan_interest_type" class="input">
                  <option value="fixed">Fixed Rate</option>
                  <option value="variable">Variable Rate</option>
                  <option value="tiered">Tiered Rate</option>
                </select>
              </FormField>

              <FormField label="Base Interest Rate (%)">
                <input
                  v-model.number="form.base_interest_rate"
                  type="number"
                  class="input"
                  placeholder="Interest Rate"
                  step="0.01"
                  min="0"
                />
              </FormField>

              <FormField label="Max Loan Amount">
                <input
                  v-model.number="form.max_loan_amount"
                  type="number"
                  class="input"
                  placeholder="Maximum Loan Amount"
                  min="0"
                />
              </FormField>

              <FormField label="Loan Duration (Months)">
                <input
                  v-model.number="form.loan_duration_months"
                  type="number"
                  class="input"
                  placeholder="Loan Duration"
                  min="1"
                />
              </FormField>
            </div>
          </div>

          <!-- Group Preferences -->
          <div class="mt-6">
            <h2 class="text-lg font-semibold mb-4">Group Preferences</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <FormField label="Group Visibility">
                <div class="flex items-center space-x-4">
                  <label class="flex items-center space-x-2">
                    <input
                      v-model="form.is_public"
                      type="radio"
                      :value="true"
                      class="form-radio"
                    />
                    <span>Public</span>
                  </label>
                  <label class="flex items-center space-x-2">
                    <input
                      v-model="form.is_public"
                      type="radio"
                      :value="false"
                      class="form-radio"
                    />
                    <span>Private</span>
                  </label>
                </div>
              </FormField>

              <FormField label="Member Invitations">
                <div class="flex items-center space-x-4">
                  <label class="flex items-center space-x-2">
                    <input
                      v-model="form.allow_member_invites"
                      type="checkbox"
                      class="form-checkbox"
                    />
                    <span>Allow Members to Invite</span>
                  </label>
                </div>
              </FormField>

              <FormField label="Loan Approval">
                <div class="flex items-center space-x-4">
                  <label class="flex items-center space-x-2">
                    <input
                      v-model="form.require_group_approval"
                      type="checkbox"
                      class="form-checkbox"
                    />
                    <span>Require Group Approval for Loans</span>
                  </label>
                </div>
              </FormField>
            </div>
          </div>

          <!-- Submit Button -->
          <div class="mt-8 flex justify-center">
            <button
              type="submit"
              class="btn btn-primary px-8 py-3"
              :disabled="form.processing"
            >
              {{ form.processing ? 'Creating...' : 'Create Group' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
