<script setup lang="ts">
import {Link} from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import EmptyStateIllustration from '@/Components/Illustrations/EmptyStateIllustration.vue'
import {Button} from "@/Components/ui/button";

withDefaults(
  defineProps<{
    can_create_group: boolean
    message?: string
  }>(), {
    message: 'You do not have any groups. Create your first group to get started!'
  })
</script>

<template>
  <AppLayout>
    <div class="container mx-auto px-4 py-8">
      <div class="max-w-md mx-auto text-center">

        <EmptyStateIllustration
          class="mx-auto mb-6 h-24 w-24"/>

        <h1 class="text-2xl font-bold mb-4">
          Looks empty here
        </h1>

        <p class="text-gray-600 dark:text-gray-300 mb-6">
          {{ message }}
        </p>

        <div class="space-y-4">
          <Button
            as-child
            class="inline-block"
            v-if="can_create_group">

            <Link
              as="button"
              :href="route('groups.create')">
              Create your first group
            </Link>

          </Button>

          <div v-else class="bg-yellow-50 dark:bg-yellow-900 p-4 rounded-lg">
            <p class="text-yellow-700 dark:text-yellow-300">
              You currently cannot create more groups.
              Consider upgrading your plan to create additional groups.
            </p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
