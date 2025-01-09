<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { ChevronDownIcon } from 'lucide-vue-next'
import {
  DropdownMenu, DropdownMenuContent, DropdownMenuItem,
  DropdownMenuTrigger
} from '@/Components/ui/dropdown-menu'

const props = defineProps<{
  availableGroups: Array<{}>,
  currentGroup?: object
}>()

const selectGroup = (group) => {
  router.post(route('groups.select', group.id), {}, {
    preserveState: true,
    onSuccess: () => {
      // Optionally redirect to group dashboard
      router.visit(route('groups.dashboard', group.id))
    }
  })
}
</script>

<template>
  <DropdownMenu>
    <DropdownMenuTrigger>
      <button class="flex items-center space-x-2">
        <span>{{ currentGroup?.name ?? 'Pick a group' }}</span>
        <ChevronDownIcon class="w-4 h-4" />
      </button>
    </DropdownMenuTrigger>

    <DropdownMenuContent>
      <DropdownMenuItem
        v-for="group in availableGroups"
        :key="group.id">
        <button
          @click="selectGroup(group)"
          class="block w-full text-left px-4 py-2 text-sm hover:bg-gray-100"
          :class="{
            'bg-primary-100': group.id === currentGroup.id
          }">
          {{ group.name }}
        </button>
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
