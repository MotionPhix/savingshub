<script setup lang="ts">
import {router, useForm} from '@inertiajs/vue3'
import {ChevronDownIcon} from 'lucide-vue-next'
import {Button} from '@/Components/ui/button'
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList, CommandSeparator,
} from '@/Components/ui/command'
import {Check} from 'lucide-vue-next'
import {computed, ref} from "vue";
import {cn} from '@/lib/utils'
import {Card, CardContent, CardDescription, CardFooter, CardTitle} from "@/Components/ui/card";
import {ComboboxItemIndicator} from "radix-vue";
import { usePluralize } from "@/composables/usePluralize"
import {isSelectElement} from "html2canvas/dist/types/dom/node-parser";

const props = defineProps<{
  groups: Array<{
    name: string
    id: number
    uuid: string
    members_count: number
    mission_statement?: string
  }>,
}>()

const searchTerm = ref('')
const { pluralizeWord } = usePluralize()

const filteredGroups = computed(() =>
  searchTerm.value === ''
    ? props.groups
    : props.groups.filter((group) => {
      return group.name.toLowerCase().includes(searchTerm.value.toLowerCase())
    })
)

const form = useForm({
  selectedGroup: {
    id: null,
    uuid: '',
    name: '',
  },
})

const onSubmit = () => {
  router.post(route('groups.set.active', form.selectedGroup.uuid), {}, {
    preserveState: true,
  })
}
</script>

<template>
  <GlobalModal
    :close-button="false"
    :close-explicitly="true"
    max-width="md" v-slot="{ close }">

    <form @submit.prevent="onSubmit">

      <Card class="p-6 dark:bg-gray-700">
        <CardTitle class="capitalize">
          Your Groups List
        </CardTitle>

        <CardDescription>
          Pick a group from the list to make active.
        </CardDescription>

        <CardContent class="p-0 py-6">
          <Command
            :display-value="(v) => v.name"
            v-model="form.selectedGroup"
            v-model:searchTerm="searchTerm">

            <CommandInput
              class="dark:border dark:border-gray-500 dark:!bg-gray-700"
              placeholder="Search groups..."/>

            <CommandEmpty>
              No group found
            </CommandEmpty>

            <CommandList
              class="h-64 dark:bg-gray-700 overflow-y-auto scrollbar-none scroll-smooth">
              <CommandGroup>
                <CommandItem
                  class="py-3 grid rounded-md somber"
                  v-for="(group, idx) in filteredGroups"
                  :key="group.id"
                  :value="group">

                  <span>{{ group.name }}</span>

                  <ComboboxItemIndicator
                    class="flex justify-between gap-2 text-muted-foreground">

                    <span class="capitalize">
                      {{ group.members_count }} {{ pluralizeWord( group.members_count, 'member' ) }}
                    </span>

                    <Check
                      :class="cn(
                        'mr-2 h-5 w-5',
                      )"
                    />
                  </ComboboxItemIndicator>

                </CommandItem>

                <CommandSeparator class="my-2" />

              </CommandGroup>
            </CommandList>
          </Command>
        </CardContent>

        <CardFooter class="flex justify-between p-0">
          <Button @click="close" type="button" variant="outline">
            Cancel
          </Button>

          <Button type="submit" class="truncate text-left">
            {{ form.selectedGroup.uuid ? 'Activate Selected' : 'Select a group' }}
          </Button>
        </CardFooter>
      </Card>
    </form>
  </GlobalModal>
</template>
