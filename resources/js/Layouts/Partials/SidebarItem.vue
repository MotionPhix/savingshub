<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import {type Component} from "vue";
import {Badge} from "@/Components/ui/badge"

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
      class="flex items-center p-2 text-base font-normal rounded-lg group transition-colors duration-200"
      :class="{
        'bg-accent text-accent-foreground': $page.url.startsWith(href),
        'hover:bg-accent/50 text-foreground hover:text-accent-foreground': !$page.url.startsWith(href)
      }"
    >
      <component
        v-if="icon"
        :is="icon"
        class="w-5 h-5 transition-colors duration-200"
        :class="{
          'text-accent-foreground': $page.url.startsWith(href),
          'text-muted-foreground group-hover:text-accent-foreground': !$page.url.startsWith(href)
        }"
      />

      <span class="ml-3 flex-1">{{ label }}</span>

      <Badge
        v-if="badge"
        variant="secondary"
        class="ml-auto"
      >
        {{ badge }}
      </Badge>
    </Link>
  </li>
</template>

<style scoped>
/* Additional theme-specific styling for hover and active states */
.group:hover {
  @apply bg-accent/50 text-accent-foreground;
}

.group:hover .icon {
  @apply text-accent-foreground;
}
</style>
