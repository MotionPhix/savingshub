<script setup lang="ts">
import {type Component, computed} from 'vue'
import {Card, CardContent} from "@/Components/ui/card";

const props = withDefaults(
  defineProps<{
    title: string
    value: string
    subtitle?: string
    icon: Component
    variant?: string
  }>(),
  {
    variant: 'warning'
  }
)

const iconClasses = computed(() => {
  const variantClasses = {
    default: 'text-primary',
    success: 'text-green-500',
    warning: 'text-yellow-500',
    destructive: 'text-red-500'
  }
  return variantClasses[props.variant]
})
</script>

<template>
  <Card>
    <CardContent class="p-4">

      <div class="mr-4 flex items-center justify-between w-full">
        <p class="text-xs text-muted-foreground mb-1">
          {{ title }}
        </p>

        <component
          :is="icon"
          class="h-5 w-5 shrink-0"
          :class="iconClasses"
        />
      </div>

      <div class="flex flex-col">
        <h3 class="text-lg font-bold">{{ value }}</h3>
        <p class="text-xs text-muted-foreground">{{ subtitle }}</p>
      </div>

    </CardContent>
  </Card>
</template>
