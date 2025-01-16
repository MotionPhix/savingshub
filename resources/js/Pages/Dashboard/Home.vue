<script setup lang="ts">
import {computed, onMounted} from 'vue'
import {router} from '@inertiajs/vue3'
import {
  PlusCircleIcon,
  DownloadIcon
} from 'lucide-vue-next'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  Card,
  CardHeader,
  CardTitle,
  CardContent
} from '@/Components/ui/card'
import {Button} from '@/Components/ui/button'

// Separate components for different role-based sections
import SuperAdminDashboard from './Partials/SuperAdminDashboard.vue'
import AdminDashboardSection from './Partials/AdminDashboardSection.vue'
import TreasurerDashboardSection from './Partials/TreasurerDashboardSection.vue'
import SecretaryDashboardSection from './Partials/SecretaryDashboardSection.vue'
import MemberDashboardSection from './Partials/MemberDashboardSection.vue'
import {toast} from "vue-sonner";
import PageHeader from "@/Components/PageHeader.vue";
import {formatCurrency} from "@/lib/formatters";

const props = withDefaults(
  defineProps<{
    user: object
    groups: Array<{}>
    userGroupRoles: object
    dashboardData: object
    analytics: {
      total_groups: number
      total_contributions: number
      total_loans: number
      recent_activities: Array<{}>
    }
    activeGroupRole?: string
  }>(), {
    activeGroupRole: 'admin',
    analytics: () => ({
      total_groups: 0,
      total_contributions: 0,
      total_loans: 0,
      recent_activities: []
    })
  }
)

console.log(props)

// Computed Properties
const isSuperAdmin = computed(() =>
  props.user.roles && props.user.roles.includes('super_admin')
)

const getDashboardSubtitle = computed(() => {
  if (isSuperAdmin.value) return 'System-wide Overview'
  return `You're viewing this as a${props.activeGroupRole === 'admin' ? 'n' : ''} ${props.activeGroupRole}`
})

const formatRelativeDate = (date) => {
  const now = new Date()
  const pastDate = new Date(date)
  const diffInDays = Math.floor((now - pastDate) / (1000 * 60 * 60 * 24))

  if (diffInDays === 0) return 'Today'
  if (diffInDays === 1) return 'Yesterday'
  if (diffInDays < 7) return `${diffInDays} days ago`
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric'
  }).format(pastDate)
}

// Action Methods
const createGroup = () => {
  router.visit(route('groups.create'))
}

const exportReport = () => {
  router.visit(route('reports.export'), {
    data: {
      type: 'dashboard',
      role: props.activeGroupRole
    }
  })
}

// Error Handling
const handleError = (error) => {
  toast.error('Dashboard Error', {
    description: error.message || 'An unexpected error occurred'
  })
}

// Role-Specific Sections Components
const getRoleSpecificComponent = computed(() => {
  if (isSuperAdmin.value) return SuperAdminDashboard

  const roleComponents = {
    'admin': AdminDashboardSection,
    'treasurer': TreasurerDashboardSection,
    'secretary': SecretaryDashboardSection,
    'member': MemberDashboardSection
  }

  return roleComponents[props.activeGroupRole] || MemberDashboardSection
})

// Permissions and Access Control
const canCreateGroup = computed(() => {
  // Add logic to determine group creation permissions
  return !isSuperAdmin.value
})

const canExportReport = computed(() => {
  // Add logic for report export permissions
  return true
})

// Additional Computed Properties for Dynamic Rendering
const hasActiveGroup = computed(() => {
  return props.groups.length > 0
})

const activeGroupName = computed(() => {
  // Get the name of the currently active group
  const activeGroup = props.groups.find(group =>
    group.id === session.get('active_group_id')
  )
  return activeGroup ? activeGroup.name : 'No Active Group'
})

// Lifecycle Hooks or Data Fetching
const fetchAdditionalData = async () => {
  try {
    // Fetch any additional data based on user role
    const response = await axios.get(route('dashboard.additional-data'), {
      params: {
        role: props.activeGroupRole
      }
    })

    // Process and store additional data
    additionalData.value = response.data
  } catch (error) {
    handleError(error)
  }
}

// Optional: Call on component mount
onMounted(() => {
  if (!isSuperAdmin.value) {
    fetchAdditionalData()
  }
})
</script>

<template>
  <AppLayout>
    <!-- Super Admin Dashboard -->
    <template v-if="isSuperAdmin">
      <SuperAdminDashboard
        :total-users="dashboardData.totalUsers"
        :total-groups="dashboardData.totalGroups"
        :total-contributions="dashboardData.totalContributions"
        :total-loans="dashboardData.totalLoans"
        :recent-activity="dashboardData.recentActivity"
      />
    </template>

    <!-- Regular User Dashboard -->
    <template v-else>
      <div class="mx-auto sm:px-4 py-8 space-y-8">
        <!-- Dashboard Header -->
        <PageHeader>
          Welcome, <br />{{ user.name }}

          <template #description>
            {{ getDashboardSubtitle }}
          </template>

          <template #action>
            <Button variant="default" @click="createGroup">
              <PlusCircleIcon class="mr-2 h-4 w-4"/>
              Create Group
            </Button>
            <Button variant="outline" @click="exportReport">
              <DownloadIcon class="mr-2 h-4 w-4"/>
              Export Report
            </Button>
          </template>
        </PageHeader>

        <!--        <div class="flex flex-col md:flex-row justify-between items-center mb-8">-->
        <!--          <div>-->
        <!--            <h1 class="text-3xl font-bold text-foreground mb-2">-->
        <!--              Welcome, {{ user.name }}-->
        <!--            </h1>-->
        <!--            <p class="text-muted-foreground">-->
        <!--              {{ getDashboardSubtitle }}-->
        <!--            </p>-->
        <!--          </div>-->

        <!--          <div class="flex space-x-4 mt-4 md:mt-0">-->
        <!--            <Button variant="default" @click="createGroup">-->
        <!--              <PlusCircleIcon class="mr-2 h-4 w-4"/>-->
        <!--              Create Group-->
        <!--            </Button>-->
        <!--            <Button variant="outline" @click="exportReport">-->
        <!--              <DownloadIcon class="mr-2 h-4 w-4"/>-->
        <!--              Export Report-->
        <!--            </Button>-->
        <!--          </div>-->
        <!--        </div>-->

        <!-- Role-Specific Dashboard Sections -->
        <template v-if="activeGroupRole === 'admin'">
          <AdminDashboardSection
            :managed-groups="dashboardData.managedGroups"
            :total-managed-groups="dashboardData.totalManagedGroups"
          />
        </template>

        <template v-else-if="activeGroupRole === 'treasurer'">
          <TreasurerDashboardSection
            :financial-overview="dashboardData.financialOverview"
            :upcoming-activities="dashboardData.upcomingFinancialActivities"
          />
        </template>

        <template v-else-if="activeGroupRole === 'secretary'">
          <SecretaryDashboardSection
            :group-management="dashboardData.groupManagement"
            :communication-tasks="dashboardData.communicationTasks"
          />
        </template>

        <template v-else>
          <MemberDashboardSection
            :personal-contributions="dashboardData.personalContributions"
            :loan-status="dashboardData.loanStatus"
          />
        </template>

        <!-- Common Sections -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <Card>
            <CardHeader>
              <CardTitle>Total Groups</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="text-2xl font-bold">
                {{ analytics.total_groups }}
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Total Contributions</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="text-2xl font-bold text-green-600">
                {{ formatCurrency(analytics.total_contributions) }}
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Total Loans</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="text-2xl font-bold text-destructive">
                {{ formatCurrency(analytics.total_loans) }}
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Recent Activities -->
        <Card>
          <CardHeader>
            <CardTitle>Recent Activities</CardTitle>
          </CardHeader>
          <CardContent>
            <div
              v-for="activity in analytics.recent_activities"
              :key="activity.date"
              class="flex justify-between items-center p-4 border-b">
              <div>
                <p class="font-medium">{{ activity.type }}</p>
                <p class="text-muted-foreground">{{ activity.group_name }}</p>
              </div>

              <div class="text-right">
                <p
                  :class="[
                    'font-semibold',
                    activity.type === 'contribution' ? 'text-green-600' : 'text-destructive'
                   ]">
                  {{ formatCurrency(activity.amount) }}
                </p>

                <p class="text-xs text-muted-foreground">
                  {{ formatRelativeDate(activity.date) }}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </template>
  </AppLayout>
</template>

<style scoped>
/* Responsive and adaptive styling */
@media (max-width: 640px) {
  .dashboard-grid {
    grid-template-columns: 1fr !important;
  }
}

/* Role-based color theming */
.role-admin {
  @apply border-l-4 border-primary;
}

.role-treasurer {
  @apply border-l-4 border-green-500;
}

.role-secretary {
  @apply border-l-4 border-blue-500;
}

.role-member {
  @apply border-l-4 border-muted-foreground;
}

/* Animated transitions */
.dashboard-section-enter-active,
.dashboard-section-leave-active {
  transition: all 0.3s ease;
}

.dashboard-section-enter-from,
.dashboard-section-leave-to {
  opacity: 0;
  transform: translateY(20px);
}
</style>
