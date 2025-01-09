<script setup>
import {ref, onMounted} from 'vue'
import {Link} from '@inertiajs/vue3'
import {
  BellIcon,
  MessageCircleIcon,
  AlertCircleIcon,
  CheckCircleIcon
} from 'lucide-vue-next'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuItem,
  DropdownMenuTrigger
} from "@/Components/ui/dropdown-menu";

const notifications = ref([])
const unreadCount = ref(0)

const getNotificationIcon = (type) => {
  const icons = {
    'message': MessageCircleIcon,
    'alert': AlertCircleIcon,
    'success': CheckCircleIcon,
    'default': BellIcon
  }
  return icons[type] || icons['default']
}

const formatTimeAgo = (timestamp) => {
  // Implement time ago formatting
  const date = new Date(timestamp)
  // Use a library like date-fns or implement custom logic
  return date.toLocaleString()
}

const handleNotificationClick = (notification) => {
  // Mark as read and navigate to related resource
  if (notification.action_url) {
    window.location.href = notification.action_url
  }
}

onMounted(async () => {
  /*try {
    // Fetch notifications via API or Inertia
    const response = await axios.get(route('notifications.recent'))
    notifications.value = response.data.notifications
    unreadCount.value = response.data.unread_count
  } catch (error) {
    console.error('Failed to fetch notifications', error)
  }*/
})
</script>

<template>
  <DropdownMenu>
    <DropdownMenuTrigger class="relative">
      <BellIcon class="w-5 h-5"/>
      <span
        v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 inline-flex items-center justify-center w-4 h-4 text-xs font-bold text-white bg-red-500 rounded-full"
      >
        {{ unreadCount }}
      </span>
    </DropdownMenuTrigger>

    <DropdownMenuContent align="end">
      <DropdownMenuLabel>
        Notifications
      </DropdownMenuLabel>

      <DropdownMenuSeparator />

      <DropdownMenuItem
        v-if="notifications.length === 0">
        No new notifications
      </DropdownMenuItem>

      <DropdownMenuItem v-else>
        <div
          v-for="notification in notifications"
          :key="notification.id"
          @click="handleNotificationClick(notification)">
          <div class="flex items-start">

            <div class="flex-shrink-0">
              <component
                :is="getNotificationIcon(notification.type)"
                class="w-5 h-5 mr-2 text-gray-500"
              />
            </div>

            <div class="ml-2">
              <p class="text-sm font-medium text-gray-900 dark:text-white">
                {{ notification.message }}
              </p>

              <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ formatTimeAgo(notification.created_at) }}
              </p>
            </div>
          </div>
        </div>
      </DropdownMenuItem>

      <DropdownMenuSeparator />

      <DropdownMenuItem>
        <Link
          href="#">
          View all notifications
        </Link>
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
