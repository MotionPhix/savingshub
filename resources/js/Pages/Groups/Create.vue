<script setup lang="ts">
import {router, Link} from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import GroupCard from "@/Pages/Groups/Partials/GroupCard.vue";
import {Button} from "@/Components/ui/button";

withDefaults(defineProps<{
  groups?: Array<{}>
  canCreateGroup: boolean
  message?: string
}>(), {
  message: 'Please select or create a group to continue',
  groups: () => []
})

const selectGroup = (group) => {
  router.post(route('groups.select', group.id), {}, {
    preserveState: true,
    onSuccess: () => {
      router.visit(route('groups.dashboard', group.id))
    }
  })
}
</script>

<template>
  <AppLayout>
    <div class="container mx-auto px-4 py-8">
      <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold mb-6 text-center">
          Select or Create a Group
        </h1>

        <!-- Existing Groups Section -->
        <section v-if="groups.length" class="mb-8">
          <h2 class="text-lg font-semibold mb-4">Your Existing Groups</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <GroupCard
              v-for="group in groups"
              :key="group.id"
              :group="group"
              @click="selectGroup(group)"
            />
          </div>
        </section>

        <!-- Create Group Section -->
        <section>
          <div v-if="canCreateGroup" class="text-center">
            <p class="mb-4 text-gray-600 dark:text-gray-400">
              {{ groups.length ? 'Or create a new group' : 'You have no groups yet' }}
            </p>

            <Button
              as-child
              class="inline-block">
              <Link
                as="button"
                :href="route('groups.create')">
                Create new group
              </Link>
            </Button>
          </div>

          <div v-else class="text-center">
            <p class="text-yellow-600">
              You have reached the maximum number of groups you can create.
            </p>
          </div>
        </section>
      </div>
    </div>
  </AppLayout>
</template>
