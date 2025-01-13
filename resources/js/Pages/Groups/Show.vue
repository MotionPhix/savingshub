<script setup lang="ts">
import {ref, computed, onUnmounted} from 'vue'
import {Head, router, usePage} from '@inertiajs/vue3'
import AppLayout from "@/Layouts/AppLayout.vue"
import { Card, CardContent } from "@/Components/ui/card"
import {Tabs, TabsContent, TabsList, TabsTrigger} from "@/Components/ui/tabs"
import {Button} from "@/Components/ui/button"
import {ActivityIcon} from "lucide-vue-next"
import MemberList from "./Partials/MemberList.vue"
import FinancialInsights from "./Partials/FinancialInsights.vue"
import ContributionChart from "./Partials/ContributionChart.vue"
import LoanAnalytics from "./Partials/LoanAnalytics.vue"
import GroupOverview from "@/Pages/Groups/Partials/GroupOverview.vue";
import {Separator} from "@/Components/ui/separator";
import {formatCurrency} from "@/lib/formatters";
import {useTabPersistence} from "@/composables/useTabPersistence";

// Props definition
const props = withDefaults(
  defineProps<{
    group: {
      id: number
      uuid: string
      name: string
      description?: string
    }
    stats: {
      pending_contributions: number
      pending_loan_requests: number
      total_members: number
    }
    members: Array<{}>
    financial_summary?: {
      total_contributions: number
    }
    contribution_insights?: object
    loan_insights?: object
    recent_activities: Array<{}>
    canManageGroup: boolean
  }>(),
  {
    stats: () => ({
      pending_contributions: 0,
      pending_loan_requests: 0,
      total_members: 0
    }),
    members: () => [],
    financial_summary: () => ({
      total_contributions: 0
    }),
    contribution_insights: () => ({}),
    loan_insights: () => ({}),
    recent_activities: () => [],
    canManageGroup: false
  }
)

// Active tab management
const currency = usePage().props.currency

// Computed properties for quick access
const groupMembers = computed(() => props.members)
const totalMembers = computed(() => props.stats.total_members || 0)
const pendingContributions = computed(() => props.stats.pending_contributions || 0)
const pendingLoans = computed(() => props.stats.pending_loan_requests || 0)

// Group actions
const editGroup = () => {
  // Navigate to group edit page
  router.visit(route('groups.edit', props.group.uuid))
}

const inviteMembers = () => {
  // Open invite members modal or navigate to invite page
  router.visit(route('groups.invite', props.group.uuid))
}

// Use tab persistence composable
const {
  activeTab,
  handleTabChange,
  clearTabPersistence
} = useTabPersistence('overview', `group_tab_${props.group.uuid}`)

// Cleanup on component unmount
onUnmounted(() => {
  clearTabPersistence()
})
</script>

<template>
  <AppLayout>
    <Head :title="group.name"/>

    <div class="mx-auto sm:px-4 py-8">
      <!-- Group Header -->
      <div class="flex flex-col sm:flex-row gap-y-4 sm:justify-between sm:items-center mb-6">
        <div>
          <h1 class="text-3xl font-bold">{{ group.name }}</h1>
          <p class="text-muted-foreground">{{ group.description }}</p>
        </div>

        <Separator class="sm:hidden"/>

        <div class="flex space-x-2">
          <Button
            v-if="canManageGroup"
            @click="editGroup"
            variant="outline">
            Edit Group
          </Button>
          <Button
            v-if="canManageGroup"
            @click="inviteMembers">
            Invite Members
          </Button>
        </div>
      </div>

      <!-- Group Summary Cards -->
      <div class="grid md:grid-cols-4 gap-4 mb-6">
        <Card>
          <CardContent class="flex items-center justify-between pt-6">
            <div>
              <h3 class="text-sm font-medium text-muted-foreground">Total Members</h3>
              <p class="text-2xl font-bold">{{ totalMembers }}</p>
            </div>

            <svg
              class="h-6 w-6 text-muted-foreground"
              xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none">
              <path
                d="M16.5 20V17.9704C16.5 16.7281 15.9407 15.5099 14.8103 14.9946C13.4315 14.3661 11.7779 14 10 14C8.22212 14 6.5685 14.3661 5.18968 14.9946C4.05927 15.5099 3.5 16.7281 3.5 17.9704V20"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              <path
                d="M20.5 20.001V17.9713C20.5 16.729 19.9407 15.5109 18.8103 14.9956C18.5497 14.8768 18.2792 14.7673 18 14.668"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="10" cy="7.5" r="3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                      stroke-linejoin="round"/>
              <path d="M15 4.14453C16.4457 4.57481 17.5 5.91408 17.5 7.49959C17.5 9.0851 16.4457 10.4244 15 10.8547"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="flex items-center justify-between pt-6">
            <div>
              <h3 class="text-sm font-medium text-muted-foreground">Total Contributions</h3>
              <p class="text-2xl font-bold">
                {{ formatCurrency(financial_summary.total_contributions || 0, currency) }}
              </p>
            </div>

            <svg
              class="h-6 w-6 text-muted-foreground"
              xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none">
              <path
                d="M20.016 2C18.9026 2 18 4.68629 18 8H20.016C20.9876 8 21.4734 8 21.7741 7.66455C22.0749 7.32909 22.0225 6.88733 21.9178 6.00381C21.6414 3.67143 20.8943 2 20.016 2Z"
                stroke="currentColor" stroke-width="1.5"/>
              <path
                d="M18 8.05426V18.6458C18 20.1575 18 20.9133 17.538 21.2108C16.7831 21.6971 15.6161 20.6774 15.0291 20.3073C14.5441 20.0014 14.3017 19.8485 14.0325 19.8397C13.7417 19.8301 13.4949 19.9768 12.9709 20.3073L11.06 21.5124C10.5445 21.8374 10.2868 22 10 22C9.71321 22 9.45546 21.8374 8.94 21.5124L7.02913 20.3073C6.54415 20.0014 6.30166 19.8485 6.03253 19.8397C5.74172 19.8301 5.49493 19.9768 4.97087 20.3073C4.38395 20.6774 3.21687 21.6971 2.46195 21.2108C2 20.9133 2 20.1575 2 18.6458V8.05426C2 5.20025 2 3.77325 2.87868 2.88663C3.75736 2 5.17157 2 8 2H20"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M6 6H14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                    stroke-linejoin="round"/>
              <path d="M8 10H6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                    stroke-linejoin="round"/>
              <path
                d="M12.5 10.875C11.6716 10.875 11 11.4626 11 12.1875C11 12.9124 11.6716 13.5 12.5 13.5C13.3284 13.5 14 14.0876 14 14.8125C14 15.5374 13.3284 16.125 12.5 16.125M12.5 10.875C13.1531 10.875 13.7087 11.2402 13.9146 11.75M12.5 10.875V10M12.5 16.125C11.8469 16.125 11.2913 15.7598 11.0854 15.25M12.5 16.125V17"
                stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="flex items-center justify-between pt-6">
            <div>
              <h3 class="text-sm font-medium text-muted-foreground">Pending Contributions</h3>
              <p class="text-2xl font-bold">{{ pendingContributions }}</p>
            </div>

            <svg
              class="h-6 w-6 text-muted-foreground"
              xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none">
              <path d="M3.3457 16.1976L16.1747 3.36866M18.6316 11.0556L16.4321 13.2551M14.5549 15.1099L13.5762 16.0886"
                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
              <path
                d="M3.17467 16.1411C1.60844 14.5749 1.60844 12.0355 3.17467 10.4693L10.4693 3.17467C12.0355 1.60844 14.5749 1.60844 16.1411 3.17467L20.8253 7.85891C22.3916 9.42514 22.3916 11.9645 20.8253 13.5307L13.5307 20.8253C11.9645 22.3916 9.42514 22.3916 7.85891 20.8253L3.17467 16.1411Z"
                stroke="currentColor" stroke-width="1.5"/>
              <path d="M4 22H20" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="flex items-center justify-between pt-6">
            <div>
              <h3 class="text-sm font-medium text-muted-foreground">Pending Loans</h3>
              <p class="text-2xl font-bold">{{ pendingLoans }}</p>
            </div>
            <ActivityIcon class="h-6 w-6 text-muted-foreground"/>
          </CardContent>
        </Card>
      </div>

      <!-- Tabs Navigation -->
      <Tabs
        v-model="activeTab" class="w-full"
        @update:modelValue="handleTabChange">
        <TabsList class="grid w-full grid-cols-5">
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="members">Members</TabsTrigger>
          <TabsTrigger value="financial">Financial</TabsTrigger>
          <TabsTrigger value="contributions">Contributions</TabsTrigger>
          <TabsTrigger value="loans">Loans</TabsTrigger>
        </TabsList>

        <!-- Tab Contents -->
        <TabsContent value="overview">
          <GroupOverview
            :group="group"
            :stats="stats"
            :recent-activities="recent_activities"
          />
        </TabsContent>

        <TabsContent value="members">
          <MemberList
            :members="members"
            :can-manage="canManageGroup"
          />
        </TabsContent>

        <TabsContent value="financial">
          <FinancialInsights
            :summary="financial_summary"
            :contribution-insights="contribution_insights"
            :loan-insights="loan_insights"
          />
        </TabsContent>

        <TabsContent value="contributions">
          <ContributionChart
            :contribution-insights="contribution_insights"
          />
        </TabsContent>

        <TabsContent value="loans">
          <LoanAnalytics
            :loan-insights="loan_insights"
          />
        </TabsContent>
      </Tabs>
    </div>
  </AppLayout>
</template>
