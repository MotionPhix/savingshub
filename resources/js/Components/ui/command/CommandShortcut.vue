<script setup>
import { cn } from '@/lib/utils.js';

const props = defineProps({
  class: { type: null, required: false },
});
</script>

<template>
  <span
    :class="
      cn('ml-auto text-xs tracking-widest text-muted-foreground', props.class)
    "
  >
    <slot />
  </span>
</template>
