<script setup lang="ts">
import { computed } from 'vue'

interface CheckboxProps {
  checked?: boolean
  value?: any
  label?: string
  disabled?: boolean
  indeterminate?: boolean
  size?: 'sm' | 'md' | 'lg'
  rounded?: 'none' | 'sm' | 'md' | 'lg' | 'full'
  color?: string
  labelPosition?: 'left' | 'right'
}

const props = withDefaults(defineProps<CheckboxProps>(), {
  checked: false,
  value: null,
  label: '',
  disabled: false,
  indeterminate: false,
  size: 'md',
  rounded: 'md',
  color: 'bg-blue-500',
  labelPosition: 'right'
})

const emit = defineEmits(['update:checked'])

// Handle checkbox change
const handleChange = () => {
  if (props.disabled) return

  emit('update:checked', !props.checked)
}

// Size configurations
const sizeConfig = {
  sm: {
    checkbox: 'w-4 h-4',
    icon: 'w-3 h-3',
    label: 'text-sm'
  },
  md: {
    checkbox: 'w-5 h-5',
    icon: 'w-4 h-4',
    label: 'text-base'
  },
  lg: {
    checkbox: 'w-6 h-6',
    icon: 'w-5 h-5',
    label: 'text-lg'
  }
}

// Computed classes
const checkboxClasses = computed(() => {
  const size = sizeConfig[props.size]
  return {
    wrapper: `
      inline-flex items-center
      ${props.labelPosition === 'right' ? 'flex-row' : 'flex-row-reverse'}
      ${props.disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'}
    `,
    checkbox: `
      ${size.checkbox}
      ${props.rounded === 'full' ? 'rounded-full' : `rounded-${props.rounded}`}
      border-2
      ${props.checked || props.indeterminate
      ? `${props.color} border-transparent`
      : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800'}
      flex items-center justify-center
      transition-all duration-200
      ${props.disabled ? 'cursor-not-allowed' : 'hover:border-opacity-70'}
    `,
    icon: `
      ${size.icon}
      text-white
      ${props.indeterminate ? 'opacity-100' : (props.checked ? 'opacity-100' : 'opacity-0')}
    `,
    label: `
      ${size.label}
      ml-2
      ${props.labelPosition === 'left' ? 'mr-2' : ''}
      ${props.disabled ? 'text-gray-400 dark:text-gray-600' : 'text-gray-700 dark:text-gray-300'}
    `
  }
})

// Indeterminate icon path
const indeterminatePath = 'M19 13H5v-2h14v2z'

// Checkmark icon path
const checkmarkPath = 'M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z'
</script>

<template>
  <label :class="checkboxClasses.wrapper">
    <div
      :class="checkboxClasses.checkbox"
      @click.prevent="handleChange"
      role="checkbox"
      :aria-checked="checked"
      :aria-disabled="disabled"
    >
      <svg
        :class="checkboxClasses.icon"
        viewBox="0 0 24 24"
        fill="currentColor"
      >
        <path
          v-if="indeterminate"
          :d="indeterminatePath"
        />
        <path
          v-else-if="checked"
          :d="checkmarkPath"
        />
      </svg>
    </div>

    <span
      v-if="label"
      :class="checkboxClasses.label"
      @click.prevent="handleChange"
    >
      {{ label }}
    </span>
  </label>
</template>
