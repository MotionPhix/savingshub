<script setup lang="ts">
import { SelectRoot, useForwardPropsEmits } from 'radix-vue';

const props = defineProps<{
  open?: boolean
  defaultOpen?: boolean
  defaultValue?: boolean
  modelValue?: string | number
  dir?: string
  name?: string
  autocomplete?: string
  disabled?: boolean
  required?: boolean
}>();

const emits = defineEmits(['update:modelValue', 'update:open']);

const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
  <SelectRoot v-bind="forwarded">
    <slot />
  </SelectRoot>
</template>
