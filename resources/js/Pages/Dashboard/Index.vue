<script setup>
import DashboardLayout from '@/Pages/Dashboard/Partials/DashboardLayout.vue'

defineProps({
  user: Object,
  groups: Array,
  userGroupRoles: Object,
  dashboardData: Object
})
</script>

<template>
  <DashboardLayout
    :user="user"
    :groups="groups"
    :user-group-roles="userGroupRoles"
    :dashboard-data="dashboardData"
  />
</template>
