<script setup lang="ts">
import {Link} from "@inertiajs/vue3";

defineProps<{
  contributions: Array<{}>
}>()
</script>

<template>
  <div>
    <h1>My Contributions</h1>

    <ul>
      <li v-for="contribution in contributions" :key="contribution.id">
        {{ contribution.amount }} on {{ contribution.contribution_date }}
      </li>
    </ul>

    <Link href="/contributions/create">
      Add Contribution
    </Link>
  </div>
</template>
