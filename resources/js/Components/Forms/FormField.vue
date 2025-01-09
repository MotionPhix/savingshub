<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: {
    type: String,
    default: ''
  },
  modelValue: {
    type: [String, Number, Boolean],
    default: ''
  },
  type: {
    type: String,
    default: 'text'
  },
  placeholder: {
    type: String,
    default: ''
  },
  required: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: ''
  },
  hint: {
    type: String,
    default: ''
  },
  icon: {
    type: Object,
    default: null
  },
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'outlined', 'underlined'].includes(value)
  }
})

defineEmits(['update:modelValue'])

// Dynamic classes for input and container
const inputClasses = computed(() => {
  const baseClasses = 'block w-full rounded-md shadow-sm focus:ring-2 focus:ring-opacity-50'

  const variantClasses = {
    default: 'border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:ring-primary-500',
    outlined: 'border-2 border-gray-300 dark:border-gray-600 focus:border-primary-500',
    underlined: 'border-0 border-b-2 border-gray-300 dark:border-gray-600 focus:border-primary-500'
  }

  const stateClasses = {
    error: 'border-red-500 focus:border-red-500 focus:ring-red-500',
    disabled: 'bg-gray-100 cursor-not-allowed opacity-50'
  }

  return [
    baseClasses,
    variantClasses[props.variant],
    props.error && stateClasses.error,
    props.disabled && stateClasses.disabled
  ].filter(Boolean).join(' ')
})

const containerClasses = computed(() => {
  return props.containerClass || 'mb-4'
})
</script>

<template>
  <div :class="containerClasses">
    <label
      v-if="label"
      :for="id"
      class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
    >
      {{ label }}
      <span v-if="required" class="text-red-500 ml-1">*</span>
    </label>

    <div class="relative">
      <slot>
        <input
          :id="id"
          :type="type"
          :value="modelValue"
          @input="$emit('update:modelValue', $event.target.value)"
          :placeholder="placeholder"
          :required="required"
          :disabled="disabled"
          :class="inputClasses"
        />
      </slot>

      <div
        v-if="icon"
        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
      >
        <component
          :is="icon"
          class="h-5 w-5 text-gray-400"
        />
      </div>

      <p
        v-if="error"
        class="mt-2 text-sm text-red-600 dark:text-red-400"
      >
        {{ error }}
      </p>
    </div>

    <p
      v-if="hint"
      class="mt-1 text-sm text-gray-500 dark:text-gray-400"
    >
      {{ hint }}
    </p>
  </div>
</template>
