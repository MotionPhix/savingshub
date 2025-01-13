<script setup>
import { cn } from '@/lib/utils.js';
import { Search } from 'lucide-vue-next';
import { ComboboxInput, useForwardProps } from 'radix-vue';
import { computed } from 'vue';

defineOptions({
  inheritAttrs: false,
});

const props = defineProps({
  type: { type: String, required: false },
  disabled: { type: Boolean, required: false },
  autoFocus: { type: Boolean, required: false },
  asChild: { type: Boolean, required: false },
  as: { type: null, required: false },
  class: { type: null, required: false },
});

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props;

  return delegated;
});

const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
  <div class="relative flex items-center" cmdk-input-wrapper>
    <Search class="left-3 h-4 w-4 shrink-0 opacity-50 absolute" />
    <ComboboxInput
      v-bind="{ ...forwardedProps, ...$attrs }"
      auto-focus
      :class="
        cn(
          'px-8 flex h-11 w-full rounded-md bg-transparent py-3 text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50',
          props.class,
        )
      "
    />
  </div>
</template>
