<script setup lang="ts">
import { ChartLineIcon } from 'lucide-vue-next'
import {type Component} from "vue";

withDefaults(
  defineProps<{
    title: string
    description: string
    icon?: Component
  }>(), {
    icon: () => ChartLineIcon
  }
)
</script>

<template>
  <div class="flex flex-col items-center justify-center h-[350px] text-center p-4">
    <component :is="icon" class="w-24 h-24 text-muted-foreground mb-4" />
    <h3 class="text-lg font-semibold mb-2">{{ title }}</h3>
    <p class="text-muted-foreground">{{ description }}</p>
  </div>
</template>
