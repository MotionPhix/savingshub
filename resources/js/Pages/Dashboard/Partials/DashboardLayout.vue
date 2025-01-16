<script setup lang="ts">
import { computed } from 'vue'
import DashboardSidebar from '@/Pages/Dashboard/Components/DashboardSidebar.vue'
import AdminDashboard from '@/Pages/Dashboard/Components/AdminDashboard.vue'
import TreasurerDashboard from '@/Pages/Dashboard/Components/TreasurerDashboard.vue'
import SecretaryDashboard from '@/Pages/Dashboard/Components/SecretaryDashboard.vue'
import MemberDashboard from '@/Pages/Dashboard/Components/MemberDashboard.vue'

const props = defineProps<{
  user: object
  groups: Array<{}>
  userGroupRoles: object,
  dashboardData: object
}>()

const dashboardComponent = computed(() => {
  const data = props.dashboardData
  console.log(data)
  if (data.managedGroups) return AdminDashboard
  if (data.financialOverview) return TreasurerDashboard
  if (data.groupManagement) return SecretaryDashboard
  return MemberDashboard
})

console.log(dashboardComponent.value)
</script>

<template>
  <div class="mx-auto px-4 py-8">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <!-- Sidebar with group quick access -->
      <DashboardSidebar
        :groups="groups"
        :user-group-roles="userGroupRoles"
      />

      <!-- Main Dashboard Content -->
      <div class="lg:col-span-2">
        <component
          :is="dashboardComponent"
          :user="user"
          :groups="groups"
          :dashboard-data="dashboardData"
        />
      </div>
    </div>
  </div>
</template>
