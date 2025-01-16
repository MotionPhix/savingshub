<script setup>
import {Card, CardContent, CardHeader, CardTitle} from "@/Components/ui/card/index.js";

const props = defineProps({
  totalUsers: Number,
  totalGroups: Number,
  totalContributions: Number,
  totalLoans: Number,
  recentActivity: Object
})

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'MWK'
  }).format(amount)
}
</script>

<template>
  <div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">System Overview</h1>

    <div class="grid md:grid-cols-4 gap-6">
      <Card>
        <CardHeader>
          <CardTitle>Total Users</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ totalUsers }}</div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Total Groups</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold">{{ totalGroups }}</div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Total Contributions</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold text-green-600">
            {{ formatCurrency(totalContributions) }}
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Total Loans</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold text-destructive">
            {{ formatCurrency(totalLoans) }}
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Recent System Activity -->
    <Card class="mt-8">
      <CardHeader>
        <CardTitle>Recent System Activity</CardTitle>
      </CardHeader>
      <CardContent>
        <div v-for="activity in recentActivity.groups" :key="activity.id">
          {{ activity.name }} created
        </div>
      </CardContent>
    </Card>
  </div>
</template>
