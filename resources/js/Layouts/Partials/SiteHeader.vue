<script setup>
import {ref, onMounted} from 'vue'
import {Link, usePage} from '@inertiajs/vue3'
import {
  MenuIcon,
  SunIcon,
  MoonIcon
} from 'lucide-vue-next'
import UserNav from './UserNav.vue'
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Notifications from "@/Components/Notifications.vue";

const appName = usePage().props.appName
const darkMode = ref(false)

const toggleDarkMode = () => {
  darkMode.value = !darkMode.value
  document.documentElement.classList.toggle('dark', darkMode.value)
  localStorage.setItem('darkMode', darkMode.value.toString())
}

const toggleMobileSidebar = () => {
  const sidebar = document.getElementById('sidebar')
  sidebar.classList.toggle('-translate-x-full')
}

onMounted(() => {
  // Check local storage for dark mode preference
  const savedDarkMode = localStorage.getItem('darkMode')
  if (savedDarkMode) {
    darkMode.value = savedDarkMode === 'true'
    document.documentElement.classList.toggle('dark', darkMode.value)
  }
})
</script>

<template>
  <header class="fixed top-0 z-50 w-full bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
    <div class="px-3 py-3 lg:px-5 lg:pl-3">
      <div class="flex items-center justify-between">
        <div class="flex items-center justify-start">

          <slot name="toggle-menu" />

          <!-- Logo -->
          <Link
            :href="route('dashboard')"
            class="flex ml-2 gap-2 md:mr-24 fill-current items-center">
            <ApplicationLogo class="h-8 w-8" />
            <span class="font-serif self-center text-xl font-semibold sm:text-3xl whitespace-nowrap dark:text-white">
              {{ appName }}
            </span>
          </Link>
        </div>

        <div class="flex items-center space-x-4">
          <!-- Dark Mode Toggle -->
          <button
            @click="toggleDarkMode"
            class="text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-4
        focus:ring-gray-200 dark:focus:ring-gray-700 rounded-lg text-sm p-2.5">
            <SunIcon v-if="darkMode" class="w-5 h-5"/>
            <MoonIcon v-else class="w-5 h-5"/>
          </button>

          <!-- Notifications -->
          <Notifications />

          <!-- User Navigation -->
          <UserNav/>
        </div>
      </div>
    </div>
  </header>
</template>
