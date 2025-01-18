<script setup lang="ts">
import {computed} from "vue";

const props = withDefaults(
  defineProps<{
    title: string
    description: string
    illustration?: string
  }>(),
  {
    illustration: 'empty-state'
  }
)

const illustrationPath = computed(() => {
  // Dynamic illustration import based on prop
  return `/illustrations/${props.illustration}.svg`
})
</script>

<template>
  <div class="flex flex-col items-center justify-center p-8 text-center">
    <div class="mb-4">
      <slot name="illustration">
        <img
          :src="illustrationPath"
          :alt="title"
          class="w-48 h-48 mx-auto"
        />
      </slot>
    </div>
    <h2 class="text-xl font-semibold mb-2">{{ title }}</h2>
    <p class="text-muted-foreground mb-4">{{ description }}</p>
    <slot></slot>
  </div>
</template>
