<script setup>
import {onMounted, onUnmounted, ref, watch} from "vue";
import {usePage} from "@inertiajs/vue3";
import 'vue3-toastify/dist/index.css';
import {toast, Toaster} from 'vue-sonner'
import SiteHeader from "@/Layouts/Partials/SiteHeader.vue";
import Sidebar from "@/Layouts/Partials/Sidebar.vue";
import SiteFooter from "@/Layouts/Partials/SiteFooter.vue";
import {Button} from "@/Components/ui/button/index.js";

const isMobile = ref(window.innerWidth < 1024)
const isSidebarOpen = ref(false)

const checkScreenSize = () => {
  const prevIsMobile = isMobile.value
  isMobile.value = window.innerWidth < 1024

  // Automatically close sidebar when switching to mobile
  if (!prevIsMobile && isMobile.value) {
    isSidebarOpen.value = false
  }
}

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value
}

const closeSidebar = () => {
  isSidebarOpen.value = false
}

// Handle escape key to close sidebar
const handleEscapeKey = (e) => {
  if (isMobile && isSidebarOpen.value && e.key === 'Escape') {
    closeSidebar()
  }
}

onMounted(() => {
  window.addEventListener('resize', checkScreenSize)
  window.addEventListener('keydown', handleEscapeKey)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkScreenSize)
  window.removeEventListener('keydown', handleEscapeKey)
})

// Watch for error messages in Inertia props
watch(
  () => usePage().props.errors,
  (errors) => {
    if (errors.message) {
      toast.error(errors.message, {
        duration: 5000,
      });
    }

    if (errors.authorization) {
      toast.warning('Access Denied', {
        description: errors.authorization,
        duration: 5000,
      })
    }
  },
  {immediate: true}
)

// Watch for error messages in Inertia props
watch(
  () => usePage().props.flush,
  (newFlush) => {

    if (newFlush !== null || newFlush !== 'null') {
      toast.warning(newFlush, {
        autoClose: 5000,
      });
    }

    usePage().props.flush = null
  },
  {immediate: true}
)
</script>

<template>
  <Toaster
    position="bottom-left"
    :expand="true" richColors/>

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
      <!-- Backdrop -->
      <Transition
        enter-active-class="transition-opacity duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-300"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="isMobile && isSidebarOpen"
          class="absolute inset-0 bg-black/50 backdrop-blur-sm"
          @click="closeSidebar"
        />
      </Transition>

      <!-- Sidebar -->
      <Transition
        enter-active-class="transition-transform duration-300 ease-out"
        enter-from-class="translate-x-[-100%]"
        enter-to-class="translate-x-0"
        leave-active-class="transition-transform duration-300 ease-in"
        leave-from-class="translate-x-0"
        leave-to-class="translate-x-[-100%]"
      >
        <div
          v-if="isMobile && isSidebarOpen"
          class="absolute top-16 bottom-0 left-0 w-64 max-w-[80%]
               bg-white dark:bg-gray-900
               shadow-lg
               transform"
          @click.stop
        >
          <Sidebar />
        </div>
      </Transition>

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

<style>
/* Prevent body scrolling when sidebar is open */
body.sidebar-open {
  overflow: hidden;
}
</style>
