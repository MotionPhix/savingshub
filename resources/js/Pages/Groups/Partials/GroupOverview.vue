<script setup lang="ts">
import {computed, ref} from 'vue'
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle
} from "@/Components/ui/card"
import {Progress} from "@/Components/ui/progress"
import {
  formatDate,
  formatCurrency
} from '@/lib/formatters'
import ActivityTimeline from "./ActivityTimeline.vue"
import {Label} from "@/Components/ui/label";
import FormField from "@/Components/Forms/FormField.vue";
import {router, usePage} from "@inertiajs/vue3";
import {Separator} from "@/Components/ui/separator";
import Toggler from "@/Components/Forms/Toggler.vue";
import Marker from "@/Components/Forms/Marker.vue";
import CalendarStartIcon from "@/Components/Icons/CalendarStartIcon.vue";
import CalendarIcon from "@/Components/Icons/CalendarIcon.vue";
import LoopIcon from "@/Components/Icons/LoopIcon.vue";
import ReceiptIcon from "@/Components/Icons/ReceiptIcon.vue";

const props = defineProps<{
  group: {
    id: number
    uuid: string
    is_public: boolean
    name: string
    description?: string
    start_date: string
    end_date: string
    duration_months: number
    contribution_frequency: string
    contribution_amount: number
    settings?: {
      currency?: string
    }
    notification_preferences?: {}
    status: string
  }
  stats: object
  recentActivities: Array<{}>
}>()

// Settings Management
const settings = ref({
  notification_preferences: props.group.notification_preferences || {},
  currency: props.group.settings?.currency || 'USD'
})

const notificationPreferences = {
  contribution_reminder: 'Contribution Reminders',
  loan_approval: 'Loan Approval Notifications',
  group_activity: 'Group Activity Updates',
  monthly_summary: 'Monthly Summary'
}

const availableCurrencies = [
  {value: 'MWK', label: 'Malawi Kwacha'},
  {value: 'USD', label: 'US Dollar'},
  {value: 'EUR', label: 'Euro'},
  {value: 'GBP', label: 'British Pound'},
  {value: 'ZAR', label: 'South African Rand'}
]

// Utility Methods
const formatNotificationPreferenceLabel = (key) =>
  notificationPreferences[key] || key

// Update Methods
const updateNotificationPreference = (key, value) => {
  console.log('Key: ', key, 'Value: ', value)

  settings.value.notification_preferences = {
    ...settings.value.notification_preferences,
    [key]: value
  }
  saveGroupSettings()
}

const updateCurrencySetting = (currency) => {
  settings.value.currency = currency
  saveGroupSettings()
}

const toggleGroupVisibility = () => {
  router.patch(route('groups.update', props.group.uuid), {
    is_public: !props.group.is_public
  })
}

const saveGroupSettings = () => {
  router.patch(route('groups.settings', props.group.uuid), {
    settings: {
      currency: settings.value.currency
    },
    notification_preferences: settings.value.notification_preferences
  })
}

// Group Lifecycle Calculation
const currentMonthInGroup = computed(() => {
  const startDate = new Date(props.group.start_date)
  const currentDate = new Date()
  const monthsDiff = (currentDate.getFullYear() - startDate.getFullYear()) * 12
    + (currentDate.getMonth() - startDate.getMonth())
    + 1 // Add 1 to make it 1-indexed

  return Math.min(monthsDiff, props.group.duration_months)
})

const groupLifecyclePercentage = computed(() => {
  return (currentMonthInGroup.value / props.group.duration_months) * 100
})

// Group Details with Icons
const groupDetails = computed(() => [
  {
    label: 'Start Date',
    value: formatDate(props.group.start_date, 'short'),
    icon: CalendarStartIcon
  },
  {
    label: 'End Date',
    value: formatDate(props.group.end_date, 'short'),
    icon: CalendarIcon
  },
  {
    label: 'Contribution Frequency',
    value: formatContributionFrequency(props.group.contribution_frequency),
    icon: LoopIcon
  },
  {
    label: 'Contribution Amount',
    value: formatCurrency(props.group.contribution_amount, usePage().props.currency as string),
    icon: ReceiptIcon
  }
])

// Utility Functions
const formatContributionFrequency = (frequency) => {
  const frequencies = {
    'weekly': 'Weekly',
    'monthly': 'Monthly',
    'quarterly': 'Quarterly',
    'annually': 'Annually'
  }
  return frequencies[frequency] || frequency
}

const getEmptyStateMessage = () => {
  if (!props.recentActivities || props.recentActivities.length === 0) {
    return props.group.status === 'pending'
      ? "Group activities will appear once the group becomes active"
      : "No recent activities in this group"
  }
  return null
}
</script>

<template>
  <div class="grid md:grid-cols-2 gap-6">
    <Card>
      <CardHeader>
        <CardTitle>
          Group Overview
        </CardTitle>
      </CardHeader>

      <CardContent>
        <div class="space-y-4">
          <!-- Group Lifecycle Progress -->
          <div class="mb-4">
            <div class="flex justify-between items-center mb-2">
              <span class="text-sm text-muted-foreground">Group Progress</span>
              <span class="text-sm text-muted-foreground">
                {{ currentMonthInGroup }} / {{ group.duration_months }} months
              </span>
            </div>
            <Progress
              :value="groupLifecyclePercentage"
              class="w-full"
            />
          </div>

          <!-- Group Details List -->
          <div
            v-for="detail in groupDetails"
            :key="detail.label"
            class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <component
                :is="detail.icon"
                class="h-5 w-5 text-muted-foreground shrink-0"
              />
              <span class="text-muted-foreground">{{ detail.label }}</span>
            </div>
            <span>{{ detail.value }}</span>
          </div>
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardHeader>
        <CardTitle>Recent Activities</CardTitle>
      </CardHeader>

      <CardContent>
        <ActivityTimeline
          :activities="recentActivities"
          :empty-state-message="getEmptyStateMessage()"
        />
      </CardContent>
    </Card>

    <!-- Group Settings Card -->
    <Card>
      <CardHeader>
        <CardTitle>Group Settings</CardTitle>
      </CardHeader>

      <CardContent>
        <form @submit.prevent="saveGroupSettings">
          <div class="space-y-4">
            <!-- Notification Preferences -->
            <div class="grid gap-y-2">
              <Label>Notification Preferences</Label>

              <Separator/>

              <div class="space-y-2 mt-2">
                <div
                  v-for="(pref, key) in notificationPreferences"
                  class="flex items-center gap-2"
                  :key="key">
                  <Marker
                    color="bg-[#22c660]"
                    :checked="settings.notification_preferences?.[key]"
                    @update:checked="(checked) => updateNotificationPreference(key, checked)"
                    :label="formatNotificationPreferenceLabel(key)"
                  />
                </div>
              </div>
            </div>

            <!-- Currency Settings -->
            <div>
              <FormField
                type="select"
                label="Group Currency"
                v-model="settings.currency"
                @update:modelValue="updateCurrencySetting"
                placeholder="Select Currency"
                :options="availableCurrencies"
              />
            </div>

            <!-- Additional Group Settings -->
            <div>
              <Label>Group Visibility</Label>
              <div class="flex items-center space-x-2 mt-2">
                <Toggler
                  id="toggle_visibility"
                  v-model="group.is_public"
                  inactiveColor="bg-red-200 dark:bg-gray-600"
                  activeColor="bg-[#22c660]"
                  rounded="md"
                  size="sm"
                />
                <Label for="toggle_visibility">
                  Make Group {{ group.is_public ? 'Private' : 'Public' }}
                </Label>
              </div>
            </div>
          </div>
        </form>
      </CardContent>
    </Card>
  </div>
</template>

<style scoped>
/* Responsive adjustments */
@media (max-width: 768px) {
  .grid {
    grid-template-columns: 1fr !important;
  }
}
</style>
