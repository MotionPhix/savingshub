<script setup>
import { Link } from '@inertiajs/vue3'
import {
  SavingsIcon,
  HomeIcon,
  WalletIcon,
  UsersIcon,
  SettingsIcon
} from 'lucide-vue-next'
import ThemeToggle from './ThemeToggle.vue'
import UserNav from './UserNav.vue'

const navigationItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Groups', href: '/groups' },
  { label: 'Contributions', href: '/contributions' },
  { label: 'Settings', href: '/settings' }
]
</script>

<template>
  <header class="sticky top-0 z-50 w-full border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
    <div class="container flex h-14 max-w-screen-2xl items-center">
      <div class="mr-4 hidden md:flex">
        <Link href="/" class="mr-6 flex items-center space-x-2">
          <SavingsIcon class="h-6 w-6" />
          <span class="hidden font-bold sm:inline-block">
            Savings Tracker
          </span>
        </Link>

        <nav class="flex items-center space-x-4 lg:space-x-6">
          <Link
            v-for="item in navigationItems"
            :key="item.href"
            :href="item.href"
            class="text-sm font-medium transition-colors hover:text-primary"
          >
            {{ item.label }}
          </Link>
        </nav>
      </div>

      <div class="flex flex-1 items-center justify-between space-x-2 md:justify-end">
        <nav class="flex items-center space-x-1">
          <ThemeToggle />
          <UserNav />
        </nav>
      </div>
    </div>
  </header>
</template>
