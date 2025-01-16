<template>
  <div class="space-y-6">
    <Card>
      <CardHeader>
        <CardTitle>Group Management</CardTitle>
        <CardDescription>Overview of group-related tasks</CardDescription>
      </CardHeader>
      <CardContent>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
          <Card
            v-for="group in groupManagement"
            :key="group.groupName"
          >
            <CardHeader>
              <CardTitle>{{ group.groupName }}</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-2">
                <div class="flex justify-between items-center">
                  <span>Pending Invitations</span>
                  <Badge
                    :variant="group.pendingInvitations > 0 ? 'destructive' : 'secondary'"
                  >
                    {{ group.pendingInvitations }}
                  </Badge>
                </div>
                <div class="flex justify-between items-center">
                  <span>Upcoming Meetings</span>
                  <Badge variant="secondary">
                    {{ group.upcomingMeetings.length }}
                  </Badge>
                </div>
              </div>
            </CardContent>
            <CardFooter>
              <Button
                variant="outline"
                class="w-full"
                @click="manageMeetings(group.groupName)">
                Manage Meetings
              </Button>
            </CardFooter>
          </Card>
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardHeader>
        <CardTitle>Communication Tasks</CardTitle>
        <CardDescription>Pending communications and invitations</CardDescription>
      </CardHeader>
      <CardContent>
        <div
          v-for="task in communicationTasks"
          :key="task.id"
          class="flex justify-between items-center p-4 border-b last:border-b-0"
        >
          <div>
            <p class="font-medium">
              {{ task.type === 'pendingInvitations' ? 'Pending Invitation' : 'Upcoming Meeting' }}
            </p>
            <p class="text-muted-foreground text-xs">
              {{ task.group }}
            </p>
          </div>
          <div class="text-right">
            <Button
              variant="outline"
              size="sm"
              @click="handleCommunicationTask(task)"
            >
              {{ task.type === 'pendingInvitations' ? 'Review' : 'Details' }}
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

<script setup>
import {
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  CardFooter
} from '@/Components/ui/card'
import {Button} from '@/Components/ui/button'
import {Badge} from '@/Components/ui/badge'
import {router} from '@inertiajs/vue3'

const props = defineProps({
  groupManagement: {
    type: Array,
    default: () => []
  },
  communicationTasks: {
    type: Array,
    default: () => []
  }
})

const manageMeetings = (groupName) => {
  router.visit(route('meetings.manage', {group: groupName}))
}

const handleCommunicationTask = (task) => {
  if (task.type === 'pendingInvitations') {
    router.visit(route('invitations.review', {group: task.group}))
  } else {
    router.visit(route('meetings.details', {meeting: task.id}))
  }
}
</script>
