<script setup lang="ts">
import { computed } from 'vue'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger
} from "@/Components/ui/dropdown-menu"
import { Button } from "@/Components/ui/button"
import {MoreVerticalIcon, RefreshCwIcon, TrashIcon, UserIcon} from "lucide-vue-next"
import UserAvatar from "@/Layouts/Partials/UserAvatar.vue";
import { useInitials } from "@/composables/useInitials";
import { formatCurrency } from "@/lib/formatters";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card"
import {Table, TableBody, TableCell, TableHead, TableHeader, TableRow} from "@/Components/ui/table";

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
    canManage?: boolean
  }>(), {
    members: () => [],
    canManage: true
  }
)

const emit = defineEmits(['action'])

const { getInitials } = useInitials()

const memberActions = computed(() => [
  {
    label: 'View Profile',
    action: (member) => emit('action', { type: 'view-profile', member }),
    icon: UserIcon
  },
  {
    label: 'Change Role',
    action: (member) => emit('action', { type: 'change-role', member }),
    icon: RefreshCwIcon,
    requireManagePermission: true
  },
  {
    label: 'Remove Member',
    action: (member) => emit('action', { type: 'remove-member', member }),
    icon: TrashIcon,
    requireManagePermission: true
  }
])

// Prevent recursive updates
const handleMemberAction = (action) => {
  action.action(action.member)
}
</script>

<template>
  <div class="space-y-4">
    <!-- Desktop View -->
    <div class="hidden md:block">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Member</TableHead>
            <TableHead>Role</TableHead>
            <TableHead>Total Contributions</TableHead>
            <TableHead>Total Loans</TableHead>
            <TableHead>Joined Date</TableHead>
            <TableHead />
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
            <TableCell align="end">
              <DropdownMenu>
                <DropdownMenuTrigger as-child>
                  <Button variant="ghost" size="icon">
                    <MoreVerticalIcon class="h-4 w-4"/>
                  </Button>
                </DropdownMenuTrigger>

                <DropdownMenuContent align="end" :side-offset="-24">
                  <DropdownMenuItem
                    :disabled="true"
                    v-for="action in memberActions.filter(a => !a.requireManagePermission || canManage)"
                    :key="action.label"
                    @click="handleMemberAction(action)">
                    <component :is="action.icon" />
                    {{ action.label }}
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <!-- Mobile View -->
    <div class="md:hidden space-y-4">
      <Card
        v-for="member in members"
        :key="member.id"
        class="hover:bg-muted/50 transition-colors">
        <CardHeader class="flex flex-row items-center space-x-4">
          <UserAvatar
            :src="member.user.avatar"
            :fallback="getInitials(member.user.name)"
            class="w-12 h-12"
          />

          <div class="flex-1">
            <CardTitle class="text-base">{{ member.user.name }}</CardTitle>
            <p class="text-xs text-muted-foreground capitalize">
              {{ member.role }} | Joined {{ member?.joined_at }}
            </p>
          </div>

          <DropdownMenu class="ml-auto">
            <DropdownMenuTrigger as-child>
              <Button variant="outline" size="icon">
                <MoreVerticalIcon class="h-4 w-4"/>
              </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="end" :side-offset="-36">
              <DropdownMenuItem
                v-for="action in memberActions.filter(a => !a.requireManagePermission || canManage)"
                :key="action.label"
                @click="handleMemberAction(action)">
                <component :is="action.icon" />
                {{ action.label }}
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </CardHeader>

        <CardContent>
          <div class="grid grid-cols-2 gap-2">
            <div>
              <p class="text-xs text-muted-foreground">Total Contributions</p>
              <p class="font-semibold">
                {{ formatCurrency(member.contribution_stats.total_paid) }}
              </p>
            </div>
            <div>
              <p class="text-xs text-muted-foreground">Total Loans</p>
              <p class="font-semibold">
                {{ formatCurrency(member.loan_stats.total_borrowed) }}
              </p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

<style scoped>
@media (max-width: 768px) {
  .member-grid {
    grid-template-columns: 1fr;
  }
}
</style>
