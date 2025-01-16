<script setup lang="ts">
import {router} from '@inertiajs/vue3'
import {
  Card, CardHeader, CardTitle, CardDescription,
  CardContent, CardFooter
} from '@/Components/ui/card'
import {Button} from '@/Components/ui/button'
import {Badge} from '@/Components/ui/badge'
import {ScrollArea} from '@/Components/ui/scroll-area'
import PageHeader from "@/Components/PageHeader.vue";

const props = defineProps<{
  groups: Array<{}>,
  userGroupRoles: object
}>()

const getBadgeVariant = (role) => {
  switch (role) {
    case 'admin':
      return 'destructive'
    case 'treasurer':
      return 'secondary'
    case 'secretary':
      return 'outline'
    default:
      return 'default'
  }
}

const selectGroup = (group) => {
  router.visit(route('groups.show', group.uuid))
}

const createNewGroup = () => {
  router.visit(route('groups.create'))
}
</script>

<template>
  <div class="sticky top-4">
    <PageHeader class="sticky top-4 z-10 bg-primary-foreground">
      My Groups

      <template #descriprion>
        Groups you are a part of
      </template>

      <template #action>
        <Button
          class="w-full"
          @click="createNewGroup">
          Create New Group
        </Button>
      </template>
    </PageHeader>

    <div v-if="groups.length === 0" class="text-muted-foreground">
      You are not a member of any groups
    </div>

    <ScrollArea v-else class="h-[400px]">
      <div v-for="group in groups" :key="group.id" class="mb-2">
        <Button
          variant="ghost"
          class="w-full justify-between"
          @click="selectGroup(group)">
          <span>{{ group.name }}</span>
          <Badge
            :variant="getBadgeVariant(userGroupRoles[group.id])">
            {{ userGroupRoles[group.id] }}
          </Badge>
        </Button>
      </div>
    </ScrollArea>
  </div>
</template>
