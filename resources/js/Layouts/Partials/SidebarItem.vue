<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import {type Component} from "vue";

const props = defineProps<{
  href: string
  label: string
  icon?: Component
  badge?: string | number
}>()
</script>

<template>
  <li>
    <Link
      :href="href"
      :class="[
        'flex items-center p-2 text-base font-normal rounded-lg group',
        $page.url.startsWith(href)
          ? 'bg-primary-100 text-primary-600 dark:bg-primary-700 dark:text-primary-200'
          : 'text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700'
      ]">
      <component
        v-if="icon"
        :is="icon"
        :class="[
          'w-5 h-5 transition duration-75',
          $page.url.startsWith(href)
            ? 'text-primary-600 dark:text-primary-200'
            : 'text-gray-500 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white'
        ]"
      />

      <span class="ml-3">{{ label }}</span>

      <span
        v-if="badge"
        class="inline-flex items-center justify-center w-5 h-5 ml-auto text-xs font-medium text-primary-600 bg-primary-100 rounded-full"
      >
        {{ badge }}
      </span>
    </Link>
  </li>
</template>
