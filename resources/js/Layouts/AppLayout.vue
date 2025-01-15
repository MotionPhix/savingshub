<script setup>
import {Toaster, toast} from 'vue-sonner'
import SiteHeader from "@/Layouts/Partials/SiteHeader.vue";
import Sidebar from "@/Layouts/Partials/Sidebar.vue";
import SiteFooter from "@/Layouts/Partials/SiteFooter.vue";
import {onMounted, onUnmounted, ref, watch} from "vue";
import {Button} from "@/Components/ui/button/index.js";
import {usePage} from "@inertiajs/vue3";

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

// Watch for error messages in Inertia props
watch(
  () => usePage().props.errors,
  (errors) => {
    // Handle generic error message
    if (errors.message) {
      toast.error(errors.message, {
        duration: 5000,
      });

      /*toast.error('Check your action', {
        description: errors.message,
        duration: 5000,
      })*/
    }

    // Optionally handle specific error types
    if (errors.authorization) {
      toast.warning('Access Denied', {
        description: errors.authorization,
        duration: 5000,
      })
    }
  },
  {immediate: true}
)
</script>

<template>
  <Toaster position="bottom-left" class="z-50" :expand="true" richColors/>

  <div class="h-screen bg-gray-50 dark:bg-gray-900 w-screen overflow-hidden">
    <SiteHeader>
      <template #toggle-menu>
        <Button
          size="icon"
          variant="ghost"
          @click="toggleSidebar">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" width="24" height="24"
               fill="none">
            <path d="M4 5L16 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                  stroke-linejoin="round"/>
            <path d="M4 12L20 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                  stroke-linejoin="round"/>
            <path d="M4 19L12 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                  stroke-linejoin="round"/>
          </svg>
        </Button>
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
          <slot/>
        </TransitionGroup>

        <SiteFooter/>
      </main>
    </div>
  </div>
</template>
