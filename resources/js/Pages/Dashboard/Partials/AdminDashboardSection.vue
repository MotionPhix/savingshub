<template>
  <Card>
    <CardHeader>
      <CardTitle>Managed Groups Overview</CardTitle>
      <CardDescription>Groups you administrate ({{ totalManagedGroups }})</CardDescription>
    </CardHeader>
    <CardContent>
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
        <Card
          v-for="group in managedGroups"
          :key="group.id"
          class="hover:shadow-lg transition-shadow"
        >
          <CardHeader>
            <CardTitle>{{ group.name }}</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-2">
              <div class="flex justify-between items-center">
                <span>Pending Members</span>
                <Badge
                  :variant="group.pendingMembers > 0 ? 'destructive' : 'secondary'"
                >
                  {{ group.pendingMembers }}
                </Badge>
              </div>
              <div class="flex justify-between items-center">
                <span>Pending Loans</span>
                <Badge
                  :variant="group.pendingLoans > 0 ? 'destructive' : 'secondary'"
                >
                  {{ group.pendingLoans }}
                </Badge>
              </div>
              <div class="flex justify-between items-center">
                <span>Pending Contributions</span>
                <Badge
                  :variant="group.pendingContributions > 0 ? 'destructive' : 'secondary'"
                >
                  {{ group.pendingContributions }}
                </Badge>
              </div>
            </div>
          </CardContent>
          <CardFooter>
            <Button
              variant="outline"
              class="w-full"
              @click="manageGroup(group.id)"
            >
              Manage Group
            </Button>
          </CardFooter>
        </Card>
      </div>
    </CardContent>
  </Card>
</template>

<script setup>
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import {
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  CardFooter
} from '@/Components/ui/card'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  managedGroups: {
    type: Array,
    default: () => []
  },
  totalManagedGroups: {
    type: Number,
    default: 0
  }
})

const manageGroup = (groupId) => {
  router.visit(route('groups.manage', { group: groupId }))
}
</script>
