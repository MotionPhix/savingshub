<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
  HomeIcon,
  WalletIcon,
  UsersIcon,
  SettingsIcon,
  SavingsIcon,
  MenuIcon,
  XIcon
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import SidebarItem from './SidebarItem.vue'

const isSidebarOpen = ref(true)

const sidebarItems = [
  {
    label: 'Dashboard',
    href: '/dashboard',
    icon: HomeIcon
  },
  {
    label: 'Groups',
    href: '/groups',
    icon: UsersIcon
  },
  {
    label: 'Contributions',
    href: '/contributions',
    icon: WalletIcon
  },
  {
    label: 'Settings',
    href: '/settings',
    icon: SettingsIcon
  }
]

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value
}

const isActiveRoute = (href) => {
  const currentRoute = usePage().component
  return currentRoute === href
}
</script>

<template>
  <aside
    class="fixed left-0 top-0 h-full w-64 bg-white dark:bg-gray-800 shadow-lg transition-all duration-300 z-40"
    :class="{ '-translate-x-full': !isSidebarOpen }"
  >
    <div class="p-4 border-b dark:border-gray-700 flex justify-between items-center">
      <Link href="/" class="flex items-center space-x-2">
        <SavingsIcon class="h-6 w-6" />
        <span class="text-xl font-bold">Savings Tracker</span>
      </Link>
      <Button
        variant="ghost"
        size="icon"
        @click="toggleSidebar"
      >
        <XIcon v-if="isSidebarOpen" class="h-5 w-5" />
        <MenuIcon v-else class="h-5 w-5" />
      </Button>
    </div>

    <nav class="p-4">
      <SidebarItem
        v-for="item in sidebarItems"
        :key="item.href"
        :href="item.href"
        :label="item.label"
        :icon="item.icon"
        :active="isActiveRoute(item.href)"
      />
    </nav>
  </aside>
</template>
