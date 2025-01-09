<script setup>
import Toaster from '@/components/ui/toast/Toaster.vue'
import SiteHeader from "@/Layouts/Partials/SiteHeader.vue";
import Sidebar from "@/Layouts/Partials/Sidebar.vue";
import SiteFooter from "@/Layouts/Partials/SiteFooter.vue";
import {onMounted, onUnmounted, ref} from "vue";
import {MenuIcon} from "lucide-vue-next";

const isMobile = ref(window.innerWidth < 1024)
const isSidebarOpen = ref(false)

const checkScreenSize = () => {
  isMobile.value = window.innerWidth < 1024

  // Automatically close sidebar on mobile
  if (isMobile.value) {
    isSidebarOpen.value = false
  }
}

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value
}

onMounted(() => {
  window.addEventListener('resize', checkScreenSize)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkScreenSize)
})
</script>

<template>
  <Toaster />

  <div class="h-screen bg-gray-50 dark:bg-gray-900 w-screen overflow-hidden">
    <SiteHeader>
      <template #toggle-menu>
        <button
          @click="toggleSidebar"
          class="p-2 text-gray-600 rounded-lg lg:hidden hover:bg-gray-100 focus:outline-none focus:ring-2
            focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600">
          <MenuIcon class="w-6 h-6"/>
        </button>
      </template>
    </SiteHeader>

    <div class="flex mt-16 h-[calc(100vh-4rem)]">
      <!-- Sidebar for Desktop -->
      <Sidebar
        v-if="!isMobile"
        class="w-64 shrink-0 border-r border-gray-200 dark:border-gray-800"
      />

      <!-- Mobile Sidebar (Overlay) -->
      <div
        v-if="isMobile && isSidebarOpen"
        class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm"
        @click="isSidebarOpen = false">
        <Sidebar
          class="w-64 mt-14 max-w-[80%] h-full bg-white dark:bg-gray-900 shadow-lg transform translate-x-0 transition-transform duration-300"
        />
      </div>

      <!-- Main Content Area -->
      <main
        class="flex-1 overflow-y-auto p-6 transition-all duration-300 ease-in-out"
        :class="{
          'lg:ml-64': !isMobile,
          'w-full': isMobile
        }">

        <TransitionGroup
          appear
          enter-active-class="transition-all duration-500 ease-out"
          enter-from-class="opacity-0 translate-y-4"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition-all duration-500 ease-in absolute inset-0"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0 -translate-y-4">
          <slot />
        </TransitionGroup>

        <SiteFooter />
      </main>
    </div>
  </div>
</template>
