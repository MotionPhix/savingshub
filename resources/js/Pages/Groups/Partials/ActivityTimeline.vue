<script setup>
import { computed } from 'vue'
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle
} from "@/Components/ui/card"
import {
  ActivityIcon,
  DollarSignIcon,
  ClockIcon
} from "lucide-vue-next"

const props = defineProps({
  activities: {
    type: Array,
    default: () => []
  }
})

const formattedActivities = computed(() => {
  return props.activities.map(activity => ({
    ...activity,
    formattedDate: new Date(activity.date).toLocaleString(),
    icon: activity.type === 'contribution'
      ? DollarSignIcon
      : activity.type === 'loan'
        ? ActivityIcon
        : ClockIcon
  })).slice(0, 10) // Limit to 10 most recent activities
})

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount || 0)
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Recent Activities</CardTitle>
    </CardHeader>
    <CardContent>
      <div class="space-y-4">
        <div
          v-for="activity in formattedActivities"
          :key="activity.id"
          class="flex items-center space-x-4 border-b pb-2 last:border-b-0"
        >
          <component
            :is="activity.icon"
            class="h-5 w-5 text-muted-foreground"
          />
          <div class="flex-1">
            <div class="font-medium">
              {{ activity.user_name }}
              {{ activity.type === 'contribution' ? 'made a contribution' : 'requested a loan' }}
            </div>
            <div class="text-sm text-muted-foreground">
              {{ formatCurrency(activity.amount) }}
              <span class="ml-2">{{ activity.formattedDate }}</span>
            </div>
          </div>
          <div
            :class="[
              'px-2 py-1 rounded-full text-xs',
              activity.status === 'pending'
                ? 'bg-yellow-100 text-yellow-800'
                : activity.status === 'paid' || activity.status === 'active'
                  ? 'bg-green-100 text-green-800'
                  : 'bg-red-100 text-red-800'
            ]"
          >
            {{ activity.status }}
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
