<script setup lang="ts">
import { computed } from 'vue'

interface ToggleProps {
  modelValue: boolean
  rounded?: 'none' | 'sm' | 'md' | 'lg' | 'full'
  size?: 'sm' | 'md' | 'lg'
  checkedPlaceholder?: string
  uncheckedPlaceholder?: string
  activeColor?: string
  inactiveColor?: string
  disabled?: boolean
}

const props = withDefaults(defineProps<ToggleProps>(), {
  rounded: 'full',
  size: 'md',
  activeColor: 'bg-blue-500',
  inactiveColor: 'bg-gray-200',
  disabled: false
})

const emit = defineEmits(['update:modelValue'])

const toggleSize = {
  sm: {
    wrapper: 'h-6 min-w-[40px]',
    indicator: 'h-5 w-5',
    padding: 'p-0.5'
  },
  md: {
    wrapper: 'h-7 min-w-[56px]',
    indicator: 'h-6 w-6',
    padding: 'p-1'
  },
  lg: {
    wrapper: 'h-9 min-w-[72px]',
    indicator: 'h-8 w-8',
    padding: 'p-1.5'
  }
}

const handleToggle = () => {
  if (!props.disabled) {
    emit('update:modelValue', !props.modelValue)
  }
}

const toggleClasses = computed(() => {
  const size = toggleSize[props.size]
  return {
    wrapper: `
      relative inline-flex items-center cursor-pointer
      ${size.wrapper}
      ${props.rounded === 'full' ? 'rounded-full' : `rounded-${props.rounded}`}
      ${props.modelValue ? props.activeColor : props.inactiveColor}
      ${props.disabled ? 'opacity-50 cursor-not-allowed' : ''}
      transition-colors duration-300
    `,
    indicator: `
      absolute bg-white shadow-md
      ${size.indicator}
      ${props.rounded === 'full' ? 'rounded-full' : `rounded-${props.rounded}`}
      ${size.padding}
      absolute top-1/2 -translate-y-1/2
      transition-transform duration-300
      flex items-center justify-center
      ${props.modelValue ? 'right-0.5' : 'left-0.5'}
    `,
    placeholder: `
      absolute top-1/2 -translate-y-1/2
      text-xs font-medium text-white
      ${props.modelValue ? 'right-2' : 'left-2'}
      ${props.modelValue ? 'opacity-100' : 'opacity-50'}
    `
  }
})

const dynamicWidth = computed(() => {
  const placeholderWidth = props.checkedPlaceholder || props.uncheckedPlaceholder
    ? (props.checkedPlaceholder?.length || props.uncheckedPlaceholder?.length) * 8 + 40
    : 0
  return placeholderWidth ? `${placeholderWidth}px` : 'auto'
})
</script>

<template>
  <button
    type="button"
    :class="toggleClasses.wrapper"
    :style="{ width: dynamicWidth }"
    @click="handleToggle"
    :disabled="disabled"
    aria-pressed="modelValue"
  >
    <!-- Indicator -->
    <div :class="toggleClasses.indicator">
      <!-- Placeholder for Unchecked State -->
      <span
        v-if="!modelValue && uncheckedPlaceholder"
        :class="toggleClasses.placeholder"
      >
        {{ uncheckedPlaceholder }}
      </span>

      <!-- Placeholder for Checked State -->
      <span
        v-if="modelValue && checkedPlaceholder"
        :class="toggleClasses.placeholder"
      >
        {{ checkedPlaceholder }}
      </span>
    </div>
  </button>
</template>
