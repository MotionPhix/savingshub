<script setup>
import {computed, onMounted, ref} from 'vue'
import {Link, router} from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  UsersIcon,
  ShieldCheckIcon,
  WalletIcon,
  CreditCardIcon
} from 'lucide-vue-next'
import UserAnalyticCard from "@/Pages/Dashboard/Partials/UserAnalyticCard.vue";
import GroupCard from "@/Pages/Dashboard/Partials/GroupCard.vue";
import {Avatar, AvatarFallback, AvatarImage} from "@/Components/ui/avatar/index.js";
import { useInitials } from "@/composables/useInitials.js";

const props = defineProps({
  user: Object,
  groups: Array,
  analytics: Object,
  canCreateGroup: Boolean
})

const defaultAvatar = '/default-avatar.png'
const { initials, getInitials } = useInitials();

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount)
}

const selectActiveGroup = (group) => {
  router.post(route('dashboard.select-group', group.id), {}, {
    preserveState: true,
    onSuccess: () => {
      router.visit(route('groups.dashboard', group.id))
    }
  })
}

// Recent Activities Section
const recentActivities = computed(() =>
  props.analytics.recent_activities || []
)

onMounted(() => {
  getInitials(props.user.name)
})
</script>

<template>
  <AppLayout>
    <div class="container mx-auto px-4 py-8">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- User Profile Summary -->
        <div class="md:col-span-1 bg-white dark:bg-gray-800 shadow rounded-lg p-6">
          <div class="flex items-center space-x-4">
            <Avatar>
              <AvatarImage
                :src="user.avatar || defaultAvatar"
                alt="User Avatar"
                class="w-16 h-16 rounded-full object-cover"
              />
              <AvatarFallback>
                {{ initials }}
              </AvatarFallback>
            </Avatar>
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
              :value="formatCurrency(analytics.total_contributions)"
              :icon="WalletIcon"
            />
            <UserAnalyticCard
              title="Total Loans"
              :value="formatCurrency(analytics.total_loans)"
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
                v-if="canCreateGroup"
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
                @select="selectActiveGroup(group)"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
