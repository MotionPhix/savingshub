<script setup lang="ts">
import { cn } from '@/lib/utils';
import { RadioGroupRoot, useForwardPropsEmits } from 'radix-vue';
import { computed } from 'vue';

const props = defineProps<{
  modelValue?: string | number | boolean
  defaultValue?: string
  disabled?: boolean
  name?: string
  required?: boolean
  orientation?: string
  dir?: string
  loop?: boolean
  asChild?: boolean
  as?: null,
  class?: null,
}>();

const emits = defineEmits(['update:modelValue']);

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props;

  return delegated;
});

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
  <RadioGroupRoot :class="cn('grid gap-2', props.class)" v-bind="forwarded">
    <slot />
  </RadioGroupRoot>
</template>
