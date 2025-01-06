<script setup>
import { computed } from 'vue'
import { format, isWithinInterval, isPast, isFuture } from 'date-fns'
import { Badge } from '@/components/ui/badge'

const props = defineProps({
  contributions: {
    type: Array,
    default: () => []
  }
})

// Sort contributions by due date (soonest first)
const sortedContributions = computed(() =>
  [...props.contributions]
    .filter(contribution => isFuture(new Date(contribution.dueDate)))
    .sort((a, b) =>
      new Date(a.dueDate) - new Date(b.dueDate)
    )
    .slice(0, 5) // Limit to 5 upcoming contributions
)

// Format contribution amount
const formatContributionAmount = (contribution) => {
  return `$${contribution.amount.toFixed(2)}`
}

// Format contribution date
const formatContributionDate = (date) => {
  return format(new Date(date), 'MMM dd, yyyy')
}

// Determine contribution status
const getContributionStatus = (contribution) => {
  const dueDate = new Date(contribution.dueDate)

  if (isPast(dueDate)) return 'Overdue'
  if (isWithinInterval( dueDate, { start: new Date(), end: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000) })) return 'Due Soon'
  return 'Upcoming'
}

// Determine badge variant based on contribution status
const getContributionBadgeVariant = (contribution) => {
  const status = getContributionStatus(contribution)
  switch (status) {
    case 'Overdue':
      return 'danger'
    case 'Due Soon':
      return 'warning'
    case 'Upcoming':
      return 'success'
    default:
      return 'default'
  }
}
</script>

<template>
  <div class="upcoming-contributions">
    <div
      v-if="sortedContributions.length === 0"
      class="text-center text-gray-500 py-4"
    >
      No upcoming contributions
    </div>

    <div
      v-else
      class="space-y-2"
    >
      <div
        v-for="contribution in sortedContributions"
        :key="contribution.id"
        class="contribution-item flex justify-between items-center p-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm"
      >
        <div>
          <h4 class="text-sm font-medium text-gray-900 dark:text-white">
            {{ contribution.group.name }}
          </h4>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ formatContributionAmount(contribution) }}
          </p>
        </div>

        <div class="flex items-center space-x-2">
          <Badge
            :variant="getContributionBadgeVariant(contribution)">
            {{ getContributionStatus(contribution) }}
          </Badge>

          <time class="text-xs text-gray-500 dark:text-gray-400">
            {{ formatContributionDate(contribution.dueDate) }}
          </time>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.upcoming-contributions {
  @apply space-y-4;
}
.contribution-item {
  @apply transition-shadow hover:shadow-lg;
}
</style>
