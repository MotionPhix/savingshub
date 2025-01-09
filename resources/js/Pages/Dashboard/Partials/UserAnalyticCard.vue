<script setup lang="ts">
import { type Component} from "vue";

const props = defineProps<{
  title: string
  value: string | number
  icon: Component
}>()
</script>

<template>
  <div class="flex items-center space-x-4 bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg">
    <div
      class="w-12 h-12 rounded-full bg-primary-100 dark:bg-primary-900
             flex items-center justify-center">
      <component
        :is="icon"
        class="w-6 h-6 text-primary-600 dark:text-primary-400"
      />
    </div>

    <div>
      <p class="text-sm text-gray-500 dark:text-gray-400">
        {{ title }}
      </p>

      <p class="text-xl font-bold text-gray-900 dark:text-white">
        {{ value }}
      </p>
    </div>
  </div>
</template>
