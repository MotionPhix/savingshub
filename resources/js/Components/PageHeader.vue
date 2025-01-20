<script setup lang="ts">
import {Separator} from "@/Components/ui/separator";

defineProps<{
  separate?: boolean
}>()
</script>

<template>

  <!-- Group Header -->
  <div class="flex flex-col sm:flex-row gap-y-4 sm:justify-between mb-6 justify-between items-start sm:items-center gap-4">
    <div>
      <h1 class="font-headings text-xl sm:text-2xl md:text-3xl font-bold">
        <slot />
      </h1>

      <p class="text-muted-foreground text-sm mt-1" v-if="$slots.description">
        <slot name="description" />
      </p>
    </div>

    <Separator v-if="separate" class="sm:hidden"/>

    <div class="flex space-x-2" v-if="$slots.action">
      <slot name="action" />
    </div>
  </div>

</template>

<style scoped>

</style>
