<script setup lang="ts">
import { type Component, computed } from 'vue'
import { v4 as uuidv4 } from 'uuid'
import { Label } from "@/Components/ui/label";
import { Input } from "@/Components/ui/input";
import InputError from "@/Components/InputError.vue";
import {Textarea} from "@/Components/ui/textarea";
import {Select, SelectContent, SelectItem, SelectTrigger, SelectValue} from "@/Components/ui/select";
import {RadioGroup, RadioGroupItem} from "@/Components/ui/radio-group";
import Checkbox from "@/Components/Checkbox.vue";

const props = withDefaults(defineProps<{
  label?: string
  type?: string
  placeholder?: string
  required?: boolean
  disabled?: boolean
  error?: string | string[]
  hint?: string
  icon?: Component
  variant?: 'default' | 'outlined' | 'underlined'
  options?: { value: string | number, label: string }[]  // For select input
  prefix?: string | Component
  suffix?: string | Component
  containerClass?: string
  orientation?: string
  autocomplete?: string
  min?: string | number
  max?: string | number
  step?: string | number
}>(), {
  type: 'text',
  required: false,
  disabled: false,
  variant: 'default',
  orientation: 'vertical'
})

const model = defineModel()
const id = uuidv4()

// Computed classes
const inputClasses = computed(() => {
  const baseClasses = '!min-h-10 block w-full rounded-md shadow-sm focus:ring-2 focus:ring-opacity-50'

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

const containerClasses = computed(() => props.containerClass || 'mb-4')
</script>

<template>
  <div :class="containerClasses">
    <slot name="label">
      <Label v-if="label" :for="id" class="mb-2">
        {{ label }} <span v-if="required" class="text-red-500 ml-1">*</span>
      </Label>
    </slot>

    <div class="relative">
      <slot name="prefix">
        <span v-if="prefix" class="absolute inset-y-0 left-3 flex items-center text-gray-500">
          <component :is="prefix" v-if="typeof prefix === 'object'" />
          <span v-else>{{ prefix }}</span>
        </span>
      </slot>

      <slot>
        <template v-if="type === 'select'">
          <Select
            :id="id"
            v-model="model"
            :required="required"
            :disabled="disabled">
            <SelectTrigger class="!min-h-10">
              <SelectValue :placeholder="placeholder" />
            </SelectTrigger>

            <SelectContent>
              <SelectItem
                v-for="option in options"
                :key="option.value"
                :value="option.value">
                {{ option.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </template>

        <template v-else-if="type === 'textarea'">
          <Textarea
            :id="id"
            class="overflow-hidden min-h-9 h-16"
            @input="(e) => {
              const target = e.target as HTMLTextAreaElement;
              target.style.height = '0px';
              target.style.height = target.scrollHeight + 'px';
            }"
            v-model="model"
            :placeholder="placeholder"
            :required="required"
            :disabled="disabled"
            :class="inputClasses"
          />
        </template>

        <template v-else-if="type === 'radio'">
          <RadioGroup
            v-model="model"
            :orientation="orientation"
            :class="{ 'flex items-center gap-4': orientation === 'horizontal' }">
            <div
              class="flex items-center space-x-2"
              v-for="option in options" :key="option.label">
              <RadioGroupItem :id="option.label" :value="option.value" />
              <Label :for="option.label">{{ option.label }}</Label>
            </div>
          </RadioGroup>
        </template>

        <template v-else-if="type === 'checkbox'">
          <div class="items-center flex gap-x-2">
            <Checkbox
              :checked="model"
              class="h-5 w-5"
              @update:checked="model != model"
              :id="id"
            />

            <label
              :for="id"
              class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
              {{ placeholder }}
            </label>
          </div>
        </template>

        <template v-else>
          <Input
            :id="id"
            :type="type"
            v-model="model"
            :placeholder="placeholder"
            :required="required"
            :disabled="disabled"
            :autocomplete="autocomplete"
            :min="min"
            :max="max"
            :step="step"
            :class="inputClasses"
          />
        </template>
      </slot>

      <slot name="suffix">
        <span v-if="suffix" class="absolute inset-y-0 right-3 flex items-center text-gray-500">
          <component :is="suffix" v-if="typeof suffix === 'object'" />
          <span v-else>{{ suffix }}</span>
        </span>
      </slot>

      <div v-if="icon" class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
        <component :is="icon" class="h-5 w-5 text-gray-400" />
      </div>

      <slot name="error">
        <InputError v-if="error" :message="Array.isArray(error) ? error.join(', ') : error" class="mt-2" />
      </slot>
    </div>

    <p v-if="hint" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
      {{ hint }}
    </p>
  </div>
</template>
