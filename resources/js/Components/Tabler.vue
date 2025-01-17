<script setup lang="ts">
import {ref, onMounted, nextTick, type Component} from 'vue'
import {Button} from '@/Components/ui/button'

const props = defineProps<{
  tabs: Array<{
    value: string
    label: string
    icon?: Component
  }>
}>()

const model = defineModel()

const emit = defineEmits(['change'])

const tabContainer = ref<HTMLElement | null>(null)

const updateTab = (tabValue: string) => {
  model.value = tabValue

  // Emit a change event
  emit('change', tabValue)

  // Scroll to selected tab
  nextTick(() => {
    const selectedTab = tabContainer.value?.querySelector(
      `[data-tab="${tabValue}"]`
    )
    if (selectedTab) {
      selectedTab.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest',
        inline: 'center'
      })
    }
  })
}

// Optional: Add touch/drag scrolling
const enableDragScroll = () => {
  if (!tabContainer.value) return

  let isDown = false
  let startX: number
  let scrollLeft: number

  const container = tabContainer.value

  container.addEventListener('mousedown', (e) => {
    isDown = true
    container.classList.add('active')
    startX = e.pageX - container.offsetLeft
    scrollLeft = container.scrollLeft
  })

  container.addEventListener('mouseleave', () => {
    isDown = false
    container.classList.remove('active')
  })

  container.addEventListener('mouseup', () => {
    isDown = false
    container.classList.remove('active')
  })

  container.addEventListener('mousemove', (e) => {
    if (!isDown) return
    e.preventDefault()
    const x = e.pageX - container.offsetLeft
    const walk = (x - startX) * 2 // Scroll-fast factor
    container.scrollLeft = scrollLeft - walk
  })
}

onMounted(() => {
  enableDragScroll()
})
</script>

<template>
  <div class="w-full">
    <div
      ref="tabContainer"
      class="flex overflow-x-auto scrollbar-hide space-x-2 pb-2"
      style="
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        scroll-behavior: smooth;
      ">
      <Button
        v-for="tab in tabs"
        :key="tab.value"
        :variant="model === tab.value ? 'default' : 'outline'"
        @click="updateTab(tab.value)"
        class="shrink-0 whitespace-nowrap flex items-center space-x-2">
        <component
          v-if="tab.icon"
          :is="tab.icon"
          class="h-4 w-4 mr-2"
        />
        {{ tab.label }}
      </Button>
    </div>
  </div>
</template>

<style scoped>
/* Hide scrollbar for Chrome, Safari and Opera */
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}

/* Hide scrollbar for IE, Edge and Firefox */
.scrollbar-hide {
  -ms-overflow-style: none; /* IE and Edge */
  scrollbar-width: none; /* Firefox */
}

/* Optional active state for drag scrolling */
.active {
  cursor: grabbing;
  cursor: -webkit-grabbing;
}
</style>
