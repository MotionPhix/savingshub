<script setup lang="ts">
import {useCurrency} from '@/composables/useCurrency'
import {Card, CardContent, CardDescription, CardHeader, CardTitle} from "@/Components/ui/card";
import {AlertCircleIcon} from "lucide-vue-next";

defineProps<{
  warning?: string
}>()
</script>

<template>
  <GlobalModal>
    <div class="grid grid-cols-1 gap-1">
    <span>
      Alert
    </span>

      <span>
        {{ warning }}
      </span>
    </div>

    <Card>
      <CardHeader>
        <CardTitle>Currency Configuration</CardTitle>
        <CardDescription>
          <AlertCircleIcon class="inline-block mr-2 text-yellow-500"/>
          Please configure your group's currency settings
        </CardDescription>
      </CardHeader>

      <CardContent>
        <select
          required>
          <option>
            USD
          </option>
        </select>
      </CardContent>
    </Card>
  </GlobalModal>
</template>
