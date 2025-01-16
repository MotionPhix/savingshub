<script setup>
import { ref } from 'vue'
import {
  UsersIcon,
  CalendarPlusIcon,
  MessageCircleIcon
} from 'lucide-vue-next'
import {
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent
} from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import { ScrollArea } from '@/Components/ui/scroll-area'

const props = defineProps({
  user: Object,
  groups: Array,
  dashboardData: Object
})

const formatDateTime = (dateTime) => {
  return new Date(dateTime).toLocaleString('en-US', {
    dateStyle: 'medium',
    timeStyle: 'short'
  })
}

const openInvitationModal = () => {
  // Implement invitation modal logic
}

const scheduleMeeting = () => {
  // Implement meeting scheduling logic
}

const sendGroupCommunication = () => {
  // Implement group communication logic
}
</script>

<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold mb-6">Group Management</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <Card>
        <CardHeader>
          <CardTitle>Group Communication</CardTitle>
          <CardDescription>Pending invitations and communications</CardDescription>
        </CardHeader>
        <CardContent>
          <div
            v-for="management in dashboardData.groupManagement"
            :key="management.groupName"
            class="mb-4 p-4 bg-muted/50 rounded-lg"
          >
            <div class="flex justify-between items-center">
              <h3 class="text-lg font-semibold">
                {{ management.groupName }}
              </h3>
              <div class="flex gap-2">
                <Badge variant="secondary">
                  Pending Invites: {{ management.pendingInvitations }}
                </Badge>
                <Badge variant="outline">
                  Upcoming Meetings: {{ management.upcomingMeetings.length }}
                </Badge>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Communication Tasks</CardTitle>
          <CardDescription>Upcoming and pending tasks</CardDescription>
        </CardHeader>
        <CardContent>
          <ScrollArea class="h-[300px]">
            <div
              v-for="task in dashboardData.communicationTasks"
              :key="task.id"
              class="mb-3 p-3 bg-background border rounded-lg"
            >
              <div class="flex justify-between items-center">
                <div>
                  <h4 class="font-medium">
                    {{ task.type }}
                  </h4>
                  <p class="text-sm text-muted-foreground">
                    {{ task.description }}
                  </p>
                </div>
                <Badge variant="outline">
                  {{ formatDate(task.date) }}
                </Badge>
              </div>
            </div>
          </ScrollArea>
        </CardContent>
      </Card>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
      <Card class="md:col-span-2">
        <CardHeader>
          <CardTitle>Upcoming Meetings</CardTitle>
          <CardDescription>Scheduled group meetings</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="dashboardData.groupManagement.some(g => g.upcomingMeetings.length)"
               class="space-y-4">
            <div
              v-for="management in dashboardData.groupManagement"
              :key="management.groupName"
            >
              <h3 class="text-lg font-semibold mb-2">
                {{ management.groupName }}
              </h3>
              <div
                v-for="meeting in management.upcomingMeetings"
                :key="meeting.id"
                class="flex justify-between items-center p-3 bg-muted/50 rounded-lg mb-2"
              >
                <div>
                  <p class="font-medium">{{ meeting.title }}</p>
                  <p class="text-sm text-muted-foreground">
                    {{ formatDateTime(meeting.scheduled_at) }}
                  </p>
                </div>
                <Badge variant="outline">
                  {{ meeting.location }}
                </Badge>
              </div>
            </div>
          </div>
          <div v-else class="text-center text-muted-foreground py-6">
            No upcoming meetings scheduled
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Quick Actions</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <Button class="w-full" @click="openInvitationModal">
            <UsersIcon class="mr-2 h-4 w-4" /> Invite Members
          </Button>
          <Button class="w-full" variant="secondary" @click="scheduleMeeting">
            <CalendarPlusIcon class="mr-2 h-4 w-4" /> Schedule Meeting
          </Button>
          <Button class="w-full" variant="outline" @click="sendGroupCommunication">
            <MessageCircleIcon class="mr-2 h-4 w-4" /> Send Group Communication
          </Button>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

<style scoped>

</style>
