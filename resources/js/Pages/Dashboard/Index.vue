<script setup lang="ts">
import {
  DollarSignIcon,
  UsersIcon,
  CalendarIcon
} from 'lucide-vue-next'
import {
  Card, CardHeader, CardContent, CardTitle
} from "@/Components/ui/card/index.js";
import AnimatedNumber from "@/Pages/Dashboard/AnimatedNumber.vue";
import UpcomingContributions from "@/Pages/Dashboard/UpcomingContributions.vue";
import ProgressChart from "@/Components/ProgressChart.vue";
import ActivityTimeline from "@/Pages/Dashboard/ActivityTimeline.vue";
import AppLayout from "@/Layouts/AppLayout.vue";

defineProps<{
  totalSavings?: number
  activeGroups?: number
  upcomingContributions?: Array<{}>
  savingsProgressData?: Array<{}>
  recentActivities?: Array<{}>
}>()
</script>

<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <Card>
          <CardHeader>
            <CardTitle>Total Savings</CardTitle>
            <DollarSignIcon/>
          </CardHeader>

          <CardContent>
            <AnimatedNumber :number="totalSavings" prefix="$"/>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Active Groups</CardTitle>
            <UsersIcon/>
          </CardHeader>

          <CardContent>
            <AnimatedNumber :number="activeGroups"/>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Upcoming Contributions</CardTitle>
            <CalendarIcon/>
          </CardHeader>

          <CardContent>
            <UpcomingContributions :contributions="upcomingContributions"/>
          </CardContent>
        </Card>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle>Savings Progress</CardTitle>
          </CardHeader>

          <CardContent>
            <ProgressChart :data="savingsProgressData"/>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Recent Activity</CardTitle>
          </CardHeader>

          <CardContent>
            <ActivityTimeline :activities="recentActivities"/>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
