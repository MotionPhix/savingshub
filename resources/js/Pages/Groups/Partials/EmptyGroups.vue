<script setup lang="ts">
import {Button} from "@/Components/ui/button";
import {router} from "@inertiajs/vue3";
</script>

<template>
  <div class="empty-state">
    <img
      src="https://images.unsplash.com/photo-1522543558187-768b6df7c25c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
      alt="Group Illustration" class="empty-image w-64 h-64 object-cover mx-auto rounded-lg"
      loading="lazy">

    <h3 class="empty-title mt-4">
      No Groups Available
    </h3>

    <p
      class="empty-description max-w-md mx-auto">
      It looks like you haven't joined any savings groups yet.
      Create or join a group today!
    </p>

    <div class="flex gap-2 w-full justify-center">
      <Button
        @click="router.get(route('groups.create'))">
        Create Group
      </Button>

      <Button
        variant="secondary">
        Discover Groups
      </Button>
    </div>
  </div>
</template>

<style scoped>
.empty-state {
  text-align: center;
  padding: 30px;
}

.empty-title {
  font-size: 1.5rem;
  font-weight: bold;
}

.empty-description {
  color: gray;
  margin-bottom: 20px;
}
</style>
