<script setup>
import {usePage} from '@inertiajs/vue3'
import SidebarItem from './SidebarItem.vue'
import {
  HomeIcon,
  UsersIcon,
  PlusCircleIcon,
  WalletIcon,
  CreditCardIcon,
  ShieldCheckIcon,
  UserIcon,
  SettingsIcon
} from 'lucide-vue-next'

const {user, userGroups} = usePage().props.auth
</script>

<template>
  <aside
    id="sidebar"
    class="w-64 fixed h-screen pt-2 transition-transform -translate-x-full bg-white border-r border-gray-200 md:translate-x-0 dark:bg-gray-800 dark:border-gray-700"
    aria-label="Sidebar">
    <div class="h-full px-3 pb-4 overflow-y-auto bg-white dark:bg-gray-800">
      <ul class="space-y-2">
        <SidebarItem
          :href="route('dashboard')"
          label="Dashboard"
          :icon="HomeIcon"
        />

        <li class="pt-4 mt-4 space-y-2 border-t border-gray-200 dark:border-gray-700">
          <span class="ml-3 text-sm font-semibold text-gray-500 uppercase dark:text-gray-400">
            Groups
          </span>
        </li>

        <SidebarItem
          :href="route('groups.index')"
          label="My Groups"
          :icon="UsersIcon"
          :badge="userGroups?.length"
        />

        <SidebarItem
          :href="route('groups.create')"
          label="Create Group"
          :icon="PlusCircleIcon"
        />

        <li class="pt-4 mt-4 space-y-2 border-t border-gray-200 dark:border-gray-700">
          <span class="ml-3 text-sm font-semibold text-gray-500 uppercase dark:text-gray-400">
            Financial
          </span>
        </li>

        <SidebarItem
          :href="route('contributions.index')"
          label="Contributions"
          :icon="WalletIcon"
        />

        <SidebarItem
          :href="route('loans.index')"
          label="Loans"
          :icon="CreditCardIcon"
        />

        <li
          v-if="user.is_admin"
          class="pt-4 mt-4 space-y-2 border-t border-gray-200 dark:border-gray-700">
          <span class="ml-3 text-sm font-semibold text-gray-500 uppercase dark:text-gray-400">
            Admin
          </span>
        </li>

        <template v-if="user.is_admin">
          <SidebarItem
            :href="route('admin.dashboard')"
            label="Admin Dashboard"
            :icon="ShieldCheckIcon"
          />

          <SidebarItem
            :href="route('admin.users')"
            label="User Management"
            :icon="UserIcon"
          />
        </template>

        <li class="pt-4 mt-4 space-y-2 border-t border-gray-200 dark:border-gray-700">
          <span class="ml-3 text-sm font-semibold text-gray-500 uppercase dark:text-gray-400">
            Account
          </span>
        </li>

        <SidebarItem
          :href="route('profile.edit')"
          label="Profile"
          :icon="SettingsIcon"
        />

        <SidebarItem
          href="#"
          label="Subscription"
          :icon="CreditCardIcon"
        />
      </ul>
    </div>
  </aside>
</template>
