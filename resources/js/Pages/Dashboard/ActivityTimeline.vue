<script setup>
import { computed } from 'vue'
import { formatDistance } from 'date-fns'

const props = defineProps({
  activities: {
    type: Array,
    default: () => []
  }
})

// Sort activities by timestamp (most recent first)
const sortedActivities = computed(() =>
  [...props.activities].sort((a, b) =>
    new Date(b.timestamp) - new Date(a.timestamp)
  )
)

// Format timestamp to human-readable format
const formatTimeAgo = (timestamp) => {
  return formatDistance(new Date(timestamp), new Date(), { addSuffix: true })
}

// Determine activity type color
const getActivityColor = (type) => {
  switch (type) {
    case 'contribution':
      return 'bg-green-500'
    case 'loan':
      return 'bg-blue-500'
    case 'group':
      return 'bg-purple-500'
    case 'withdrawal':
      return 'bg-red-500'
    default:
      return 'bg-gray-500'
  }
}
</script>

<template>
  <div class="activity-timeline">
    <div
      v-for="(activity, index) in sortedActivities"
      :key="activity.id"
      class="timeline-item relative pl-8 pb-8 border-l-2 border-gray-200 dark:border-gray-700"
    >
      <!-- Timeline marker -->
      <span
        class="absolute left-0 top-2 w-4 h-4 rounded-full"
        :class="getActivityColor(activity.type)"
      ></span>

      <!-- Activity content -->
      <div class="timeline-content">
        <div class="flex justify-between items-center mb-2">
          <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
            {{ activity.title }}
          </h4>
          <time class="text-xs text-gray-500 dark:text-gray-400">
            {{ formatTimeAgo(activity.timestamp) }}
          </time>
        </div>

        <p class="text-sm text-gray-600 dark:text-gray-300">
          {{ activity.description }}
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.activity-timeline {
  @apply space-y-4;
}
.timeline-item:last-child {
  @apply border-l-0;
}
</style>
