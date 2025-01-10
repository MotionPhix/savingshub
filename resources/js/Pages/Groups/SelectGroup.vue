<script setup lang="ts">
import {router} from '@inertiajs/vue3'
import {ChevronDownIcon} from 'lucide-vue-next'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger
} from '@/Components/ui/dropdown-menu'

const props = defineProps<{
  groups: Array<{}>,
}>()

const selectGroup = (group) => {
  router.post(route('groups.set.active', group.uuid), {}, {
    preserveState: true,
  })
}
</script>

<template>
  <GlobalModal max-width="sm">
    <DropdownMenu>
      <DropdownMenuTrigger>
        <button class="flex items-center space-x-2">
          <span>Pick a group</span>
          <ChevronDownIcon class="w-4 h-4"/>
        </button>
      </DropdownMenuTrigger>

      <DropdownMenuContent>
        <DropdownMenuItem
          v-for="group in groups"
          :key="group.id"
          @click="selectGroup(group)">
          {{ group.name }}
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  </GlobalModal>
</template>
