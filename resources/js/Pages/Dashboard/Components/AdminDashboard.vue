<script setup>
import { UsersIcon, SettingsIcon } from 'lucide-vue-next'
import {
  Card, CardHeader, CardTitle, CardDescription, CardContent
} from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'

const props = defineProps({
  user: Object,
  groups: Array,
  dashboardData: Object
})

const inviteMembers = () => {
  // Implement member invitation logic
}

const manageGroupSettings = () => {
  // Implement group settings management
}
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold mb-6">Group Administration</h1>

    <div class="grid md:grid-cols-2 gap-6">
      <Card>
        <CardHeader>
          <CardTitle>Managed Groups</CardTitle>
          <CardDescription>
            Groups you are administering
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div
            v-for="group in dashboardData.managedGroups"
            :key="group.id"
            class="mb-4"
          >
            <div class="flex justify-between items-center">
              <span>{{ group.name }}</span>
              <div class="flex space-x-2">
                <Badge variant="secondary">
                  Pending Members: {{ group.pendingMembers }}
                </Badge>
                <Badge variant="destructive">
                  Pending Loans: {{ group.pendingLoans }}
                </Badge>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Quick Actions</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid gap-4">
            <Button @click="inviteMembers">
              <UsersIcon class="mr-2 h-4 w-4" /> Invite Members
            </Button>
            <Button variant="secondary" @click="manageGroupSettings">
              <SettingsIcon class="mr-2 h-4 w-4" /> Group Settings
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
