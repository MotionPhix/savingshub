<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { gsap } from 'gsap'

const props = defineProps({
  number: {
    type: [Number, String],
    required: true
  },
  duration: {
    type: Number,
    default: 1.5
  },
  prefix: {
    type: String,
    default: ''
  },
  suffix: {
    type: String,
    default: ''
  },
  decimals: {
    type: Number,
    default: 2
  },
  delay: {
    type: Number,
    default: 0
  }
})

const numberElement = ref(null)
const displayValue = ref(0)

// Format number with specified decimals
const formatNumber = (value) => {
  return Number(value).toLocaleString('en-US', {
    minimumFractionDigits: props.decimals,
    maximumFractionDigits: props.decimals
  })
}

// Computed formatted display value
const formattedDisplayValue = computed(() => {
  return `${props.prefix}${formatNumber(displayValue.value)}${props.suffix}`
})

// Animation method
const animateNumber = () => {
  // Ensure we have a valid number and reference
  if (!numberElement.value) return

  gsap.to(displayValue, {
    value: Number(props.number),
    duration: props.duration,
    delay: props.delay,
    ease: 'power2.out',
    onUpdate: () => {
      // Optional: Add any additional update logic
    }
  })
}

// Watch for number changes
watch(() => props.number, (newValue, oldValue) => {
  if (newValue !== oldValue) {
    animateNumber()
  }
}, { immediate: true })

// Lifecycle hooks
onMounted(() => {
  animateNumber()
})

// Cleanup to prevent memory leaks
onUnmounted(() => {
  // If using GSAP, kill any running animations
  gsap.killTweensOf(displayValue)
})
</script>

<template>
  <div class="animated-number">
    <span ref="numberElement" class="text-4xl font-bold text-primary">
      {{ formattedDisplayValue }}
    </span>
  </div>
</template>

<style scoped>
.animated-number {
  @apply inline-block;
}
</style>
