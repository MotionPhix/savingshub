<script setup lang="ts">
import {ref} from 'vue'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from "@/Components/ui/table"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger
} from "@/Components/ui/dropdown-menu"
import {Button} from "@/Components/ui/button"
import {MoreVerticalIcon} from "lucide-vue-next"
import UserAvatar from "@/Layouts/Partials/UserAvatar.vue";
import {useInitials} from "@/composables/useInitials.js";
import {formatCurrency} from "@/lib/formatters";

const props = withDefaults(
  defineProps<{
    members: Array<{
      id: number
      user: {
        id: number
        name: string
        email?: string
        avatar?: string
        uuid: string
      }
      joined_at?: string
      role: string
      total_contributions?: number
      total_loans?: number
      contribution_stats: {
        total_paid: number
        pending_count: number
        overdue_count: number
      },
      loan_stats: {
        total_borrowed: number
        pending_count: number
        active_count: number
        overdue_count: number
      }
    }>
    canManage: boolean
  }>(), {
    members: () => [],
    canManage: false
  }
)

const {getInitials} = useInitials()
const selectedMember = ref(null)

const memberActions = [
  {
    label: 'View Profile',
    action: (member) => {
      // Navigate to user profile
    }
  },
  {
    label: 'Change Role',
    action: (member) => {
      // Open role change modal
    },
    requireManagePermission: true
  },
  {
    label: 'Remove Member',
    action: (member) => {
      // Open remove member confirmation
    },
    requireManagePermission: true
  }
]
</script>

<template>
  <div>
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>Member</TableHead>
          <TableHead>Role</TableHead>
          <TableHead>Total Contributions</TableHead>
          <TableHead>Total Loans</TableHead>
          <TableHead>Joined Date</TableHead>
          <TableHead>Actions</TableHead>
        </TableRow>
      </TableHeader>

      <TableBody>
        <TableRow v-for="member in members" :key="member.id">
          <TableCell>
            <div class="flex items-center space-x-3">
              <UserAvatar
                :src="member.user.avatar"
                :fallback="getInitials(member.user.name)"
              />
              <div>
                <div>{{ member.user.name }}</div>
                <div class="text-muted-foreground">{{ member.user?.email }}</div>
              </div>
            </div>
          </TableCell>
          <TableCell class="capitalize">{{ member.role }}</TableCell>
          <TableCell>
            {{ formatCurrency(member.contribution_stats.total_paid) }}
          </TableCell>

          <TableCell>
            {{ formatCurrency(member.loan_stats.total_borrowed) }}
          </TableCell>
          <TableCell>{{ member?.joined_at }}</TableCell>
          <TableCell>
            <DropdownMenu>
              <DropdownMenuTrigger as-child>
                <Button variant="ghost" size="icon">
                  <MoreVerticalIcon class="h-4 w-4"/>
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem
                  v-for="action in memberActions.filter(a => !a.requireManagePermission || canManage)"
                  :key="action.label"
                  @click="action.action(member)">
                  {{ action.label }}
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </TableCell>
        </TableRow>
      </TableBody>
    </Table>
  </div>
</template>
