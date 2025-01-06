<script setup>
import AnimatedRouter from '@/components/AnimatedRouter.vue'
import Toaster from '@/components/ui/toast/Toaster.vue'
import SiteHeader from "@/Layouts/Partials/SiteHeader.vue";
import Sidebar from "@/Layouts/Partials/Sidebar.vue";
import SiteFooter from "@/Layouts/Partials/SiteFooter.vue";
</script>

<template>
  <Toaster />

  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <SiteHeader />
    <div class="flex">
      <Sidebar />
      <main class="flex-1 p-6 transition-all duration-300 ease-in-out">
        <AnimatedRouter />
      </main>
    </div>
    <SiteFooter />
    <ThemeToggle />
    <Toaster />
  </div>
</template>
