<script setup lang="ts">
import {computed} from 'vue'
import {Link, router, usePage} from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  UsersIcon,
  ShieldCheckIcon,
  WalletIcon,
  CreditCardIcon
} from 'lucide-vue-next'
import UserAnalyticCard from "@/Pages/Dashboard/Partials/UserAnalyticCard.vue";
import GroupCard from "@/Pages/Dashboard/Partials/GroupCard.vue";
import UserAvatar from "@/Layouts/Partials/UserAvatar.vue";
import {useInitials} from "@/composables/useInitials";
import {formatCurrency} from "@/lib/formatters";

const props = defineProps<{
  user: object
  groups: Array<{}>
  analytics: object
}>()

const { getInitials } = useInitials()
const currency: string = <string>usePage().props.currency

const setGroup = (group) => {
  router.post(route('groups.set.active', group.uuid), {}, {
    preserveState: true,
    onSuccess: () => {
      router.visit(route('groups.show', group.uuid), { replace: true })
    }
  })
}

// Recent Activities Section
const recentActivities = computed(() =>
  props.analytics.recent_activities || []
)
</script>

<template>
  <AppLayout>
    <div class="mx-auto sm:px-4 py-8">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- User Profile Summary -->
        <div class="md:col-span-1 bg-white dark:bg-gray-800 shadow rounded-lg p-6">
          <div class="flex flex-row sm:flex-col lg:flex-row lg:items-center gap-4 lg:gap-0 lg:space-x-4 overflow-x-clip">
            <UserAvatar :size="16" :fallback="getInitials(user.name)"/>

            <div>
              <h2 class="text-xl font-semibold">{{ user.name }}</h2>
              <p class="text-gray-500">{{ user.email }}</p>
            </div>
          </div>

          <div class="mt-6 space-y-4">
            <UserAnalyticCard
              title="Total Groups"
              :value="analytics.total_groups"
              :icon="UsersIcon"
            />

            <UserAnalyticCard
              title="Owned Groups"
              :value="analytics.owned_groups"
              :icon="ShieldCheckIcon"
            />

            <UserAnalyticCard
              title="Total Contributions"
              :value="formatCurrency(analytics.total_contributions, currency)"
              :icon="WalletIcon"
            />

            <UserAnalyticCard
              title="Total Loans"
              :value="formatCurrency(analytics.total_loans, currency)"
              :icon="CreditCardIcon"
            />
          </div>
        </div>

        <!-- Groups and Recent Activity -->
        <div class="md:col-span-2 space-y-6">
          <!-- Groups Section -->
          <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold mb-4">My Groups</h3>

            <div
              v-if="groups.length === 0"
              class="text-center text-gray-500 py-4">
              You are not a member of any groups yet.
              <Link
                as="button"
                v-if="$page.props.auth.can.create_group"
                :href="route('groups.create')"
                class="ml-2 text-primary-600 hover:underline">
                Create a Group
              </Link>
            </div>

            <div
              v-else
              class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <GroupCard
                v-for="group in groups"
                :key="group.id"
                :group="group"
                @select="setGroup(group)"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
