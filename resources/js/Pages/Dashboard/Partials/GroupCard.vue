<script setup lang="ts">
import { ChevronRightIcon } from 'lucide-vue-next'
import {useInitials} from "@/composables/useInitials";
import {formatCurrency} from "../../../lib/formatters";
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
  group: {
    type: Object,
    required: true
  }
})

defineEmits(['select'])

const { getInitials } = useInitials()
const currency = usePage().props.currency

const getGroupColor = (id) => {
  // Generate a consistent color based on group ID
  const colors = [
    '#3B82F6', '#10B981', '#6366F1',
    '#F43F5E', '#8B5CF6', '#F59E0B'
  ]
  return colors[id % colors.length]
}

const getMemberRole = (group) => {
  const membership = group.members[0]
  switch (membership.role) {
    case 'admin': return 'Group Admin'
    case 'treasurer': return 'Treasurer'
    case 'secretary': return 'Secretary'
    default: return 'Member'
  }
}
</script>

<template>
  <div
    class="bg-white dark:bg-gray-700 shadow rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer"
    @click="$emit('select')">

    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center space-x-3">
        <div
          class="w-12 h-12 rounded-full flex items-center justify-center"
          :style="{ backgroundColor: getGroupColor(group.id) }">
          <span class="text-white font-bold">
            {{ getInitials(group.name).toUpperCase() }}
          </span>
        </div>

        <div>
          <h4 class="text-lg font-semibold">{{ group.name }}</h4>
          <p class="text-sm text-gray-500">
            {{ getMemberRole(group) }}
          </p>
        </div>
      </div>

      <ChevronRightIcon class="w-5 h-5 text-gray-400" />
    </div>

    <div class="grid grid-cols-3 gap-2 text-center">
      <div>
        <p class="text-xs text-gray-500">Members</p>
        <p class="font-bold">{{ group.members_count }}</p>
      </div>

      <div>

        <p class="text-xs text-gray-500">Contributions</p>
        <p class="font-bold">
          {{ formatCurrency(group.total_contributions || 0, currency) }}
        </p>

      </div>

      <div>
        <p class="text-xs text-gray-500">Loans</p>
        <p class="font-bold">
          {{ formatCurrency(group.total_loans || 0, currency) }}
        </p>
      </div>

    </div>

  </div>
</template>
