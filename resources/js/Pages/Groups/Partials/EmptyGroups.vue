<script setup lang="ts">
import {Button} from "@/Components/ui/button";
</script>

<template>
  <div class="empty-state">
    <img src="/assets/empty.svg" alt="No Data" class="empty-image">
    <h3 class="empty-title">No Groups Available</h3>
    <p class="empty-description">Start by creating a new group to manage your contributions.</p>
    <Button @click="$emit('create')">Create Group</Button>
  </div>
</template>

<style scoped>
.empty-state {
  text-align: center;
  padding: 40px;
}
.empty-image {
  max-width: 200px;
  margin: 0 auto 20px;
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
