<script setup lang="ts">
import {computed} from 'vue'
import {UsersIcon, WalletIcon} from 'lucide-vue-next'
import { Avatar, AvatarFallback, AvatarImage} from "@/Components/ui/avatar";
import {useInitials} from "@/composables/useInitials";
import {formatCurrency} from "@/lib/formatters";

const props = defineProps({
  group: {
    type: Object,
    required: true
  }
})

// Emit click event for selection
defineEmits(['click'])

const { getInitials } = useInitials()

// Compute limited members for avatar display
const limitedMembers = computed(() => {
  return props.group.members ? props.group.members.slice(0, 3) : []
})

// Get member's role in the group
const getMemberRole = () => {
  const membership = props.group.members?.find(
    member => member.user_id === window.Laravel.user.id
  )
  return membership ? membership.role : 'Member'
}

// Determine badge color based on role
const getRoleClasses = () => {
  const role = getMemberRole().toLowerCase()
  const roleClasses = {
    admin: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    treasurer: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    secretary: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
    default: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
  }
  return roleClasses[role] || roleClasses.default
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return new Intl.RelativeTimeFormat('en', {
    numeric: 'auto'
  }).format(
    Math.round((date - new Date()) / (1000 * 60 * 60 * 24)),
    'day'
  )
}
</script>

<template>
  <div
    class="group-card bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 cursor-pointer
           transition-all duration-300 hover:shadow-lg hover:border-primary-500
           border border-transparent"
    @click="$emit('click')">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
        {{ group.name }}
      </h3>

      <div
        class="badge px-2 py-1 rounded-full text-xs font-medium"
        :class="getRoleClasses()"
      >
        {{ getMemberRole() }}
      </div>
    </div>

    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-2">
        <UsersIcon class="w-5 h-5 text-gray-500 dark:text-gray-400"/>
        <span class="text-sm text-gray-600 dark:text-gray-300">
          {{ group.members_count }} Members
        </span>
      </div>

      <div class="flex items-center space-x-2">
        <WalletIcon class="w-5 h-5 text-gray-500 dark:text-gray-400"/>
        <span class="text-sm text-gray-600 dark:text-gray-300 font-figures">
          {{ formatCurrency(group.total_contributions || 0) }}
        </span>
      </div>
    </div>

    <div v-if="group.description" class="mt-4">
      <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
        {{ group.description }}
      </p>
    </div>

    <div class="mt-4 flex items-center justify-between">
      <div class="flex -space-x-2">
        <Avatar
          v-for="member in limitedMembers"
          :key="member.id">
          <AvatarImage
            :src="member.profile_photo_url"
            :alt="member.name"
            class="w-8 h-8 rounded-full border-2 border-white dark:border-gray-800"
          />
          <AvatarFallback>
            {{ getInitials(member.name) }}
          </AvatarFallback>
        </Avatar>

        <div
          v-if="group.members_count > 3"
          class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700
                 flex items-center justify-center text-xs text-gray-600 dark:text-gray-300"
        >
          +{{ group.members_count - 3 }}
        </div>
      </div>

      <div class="text-sm text-gray-500 dark:text-gray-400">
        Created {{ formatDate(group.created_at) }}
      </div>
    </div>
  </div>
</template>

<style scoped>
.group-card {
  transition: all 0.3s ease;
}

.group-card:hover {
  transform: translateY(-5px);
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
