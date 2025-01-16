<script setup lang="ts">
import {ref, onMounted} from 'vue'
import {Link, usePage} from '@inertiajs/vue3'
import {
  SunIcon,
  MoonIcon
} from 'lucide-vue-next'
import UserNav from './UserNav.vue'
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Notifications from "@/Components/Notifications.vue";
import {
  Tooltip,
  TooltipProvider,
  TooltipTrigger,
  TooltipContent,
} from "@/Components/ui/tooltip";
import {Button} from "@/Components/ui/button";

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
  <header
    class="fixed top-0 z-50 w-full bg-background border-b border-border"
  >
    <div class="px-3 py-3 lg:px-5 lg:pl-3">
      <div class="flex items-center justify-between">
        <div class="flex items-center justify-start">
          <slot name="toggle-menu" />

          <!-- Logo -->
          <Link
            :href="route('dashboard')"
            class="flex ml-2 gap-2 md:mr-24 fill-current items-center group">
            <ApplicationLogo class="h-8 w-8 group-hover:scale-105 transition-transform" />
            <span
              class="font-serif self-center text-xl font-semibold sm:text-2xl whitespace-nowrap text-foreground group-hover:text-primary transition-colors"
            >
              {{ appName }}
            </span>
          </Link>
        </div>

        <div class="flex items-center space-x-4">
          <!-- Dark Mode Toggle -->
          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger>
                <Button
                  variant="ghost"
                  size="icon"
                  @click="toggleDarkMode"
                  class="text-muted-foreground hover:text-foreground"
                >
                  <SunIcon v-if="darkMode" class="w-5 h-5"/>
                  <MoonIcon v-else class="w-5 h-5"/>
                </Button>
              </TooltipTrigger>
              <TooltipContent>
                {{ darkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode' }}
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>

          <!-- Notifications -->
          <Notifications />

          <!-- User Navigation -->
          <UserNav/>
        </div>
      </div>
    </div>
  </header>
</template>

<style scoped>
/* Additional theme-specific styling */
.logo-container {
  @apply transition-all duration-300 ease-in-out;
}

.logo-container:hover {
  @apply scale-105;
}
</style>
